<?php
namespace josemmo\Verifactu\Models\Responses;

use DateTimeImmutable;
use DateTimeInterface;
use josemmo\Verifactu\Exceptions\AeatException;
use josemmo\Verifactu\Models\Model;
use josemmo\Verifactu\Models\Records\InvoiceIdentifier;
use josemmo\Verifactu\Models\Records\InvoiceType;
use josemmo\Verifactu\Models\Records\Record;
use josemmo\Verifactu\Services\AeatClient;
use UXML\UXML;

/**
 * Response from AEAT ConsultaLR service
 *
 * @field RespuestaConsultaFactuSistemaFacturacion
 */
class ConsultaResponse extends Model {
    /** Response XML namespace */
    public const NS = 'https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/RespuestaConsultaLR.xsd';

    /**
     * Create new instance from XML response
     *
     * @param UXML $xml Raw XML response
     *
     * @return ConsultaResponse Parsed response
     *
     * @throws AeatException if server returned an error or failed to parse response
     */
    public static function from(UXML $xml): self {
        $nsEnv = AeatClient::NS_SOAPENV;
        $nsConR = self::NS;
        $nsTik = Record::NS;
        $instance = new self();

        // Handle server errors
        $faultElement = $xml->get("{{$nsEnv}}Body/{{$nsEnv}}Fault/faultstring");
        if ($faultElement !== null) {
            throw new AeatException($faultElement->asText());
        }

        // Get root XML element
        $rootXml = $xml->get("{{$nsEnv}}Body/{{$nsConR}}RespuestaConsultaFactuSistemaFacturacion");
        if ($rootXml === null) {
            throw new AeatException('Missing <conR:RespuestaConsultaFactuSistemaFacturacion /> element from response');
        }

        // Parse pagination indicator
        $paginationElement = $rootXml->get("{{$nsConR}}IndicadorPaginacion");
        $instance->hasMorePages = ($paginationElement !== null && $paginationElement->asText() === 'S');

        // Parse next pagination key
        $nextPageElement = $rootXml->get("{{$nsConR}}ClavePaginacion");
        if ($nextPageElement !== null) {
            $nextPaginationId = new InvoiceIdentifier();

            $issuerIdElement = $nextPageElement->get("{{$nsTik}}IDEmisorFactura");
            if ($issuerIdElement !== null) {
                $nextPaginationId->issuerId = $issuerIdElement->asText();
            }

            $invoiceNumberElement = $nextPageElement->get("{{$nsTik}}NumSerieFactura");
            if ($invoiceNumberElement !== null) {
                $nextPaginationId->invoiceNumber = $invoiceNumberElement->asText();
            }

            $issueDateElement = $nextPageElement->get("{{$nsTik}}FechaExpedicionFactura");
            if ($issueDateElement !== null) {
                $issueDate = DateTimeImmutable::createFromFormat('d-m-Y', $issueDateElement->asText());
                if ($issueDate === false) {
                    throw new AeatException('Invalid pagination key issue date: ' . $issueDateElement->asText());
                }
                $nextPaginationId->issueDate = $issueDate->setTime(0, 0, 0, 0);
            }

            $instance->nextPaginationId = $nextPaginationId;
        }

        // Parse records
        foreach ($rootXml->getAll("{{$nsConR}}RegistroRespuestaConsultaFactu") as $recordElement) {
            $item = new ConsultaResponseItem();
            $item->invoiceId = new InvoiceIdentifier();

            // Parse invoice ID
            $idFacturaElement = $recordElement->get("{{$nsConR}}IDFactura");
            if ($idFacturaElement !== null) {
                $issuerIdElement = $idFacturaElement->get("{{$nsTik}}IDEmisorFactura");
                if ($issuerIdElement !== null) {
                    $item->invoiceId->issuerId = $issuerIdElement->asText();
                }

                $invoiceNumberElement = $idFacturaElement->get("{{$nsTik}}NumSerieFactura");
                if ($invoiceNumberElement !== null) {
                    $item->invoiceId->invoiceNumber = $invoiceNumberElement->asText();
                }

                $issueDateElement = $idFacturaElement->get("{{$nsTik}}FechaExpedicionFactura");
                if ($issueDateElement !== null) {
                    $issueDate = DateTimeImmutable::createFromFormat('d-m-Y', $issueDateElement->asText());
                    if ($issueDate === false) {
                        throw new AeatException('Invalid invoice issue date: ' . $issueDateElement->asText());
                    }
                    $item->invoiceId->issueDate = $issueDate->setTime(0, 0, 0, 0);
                }
            }

            // Parse issuer name
            $issuerNameElement = $recordElement->get("{{$nsConR}}NombreRazonEmisor");
            if ($issuerNameElement !== null) {
                $item->issuerName = $issuerNameElement->asText();
            }

            // Parse invoice type
            $invoiceTypeElement = $recordElement->get("{{$nsConR}}TipoFactura");
            if ($invoiceTypeElement !== null) {
                $item->invoiceType = InvoiceType::from($invoiceTypeElement->asText());
            }

            // Parse description
            $descriptionElement = $recordElement->get("{{$nsConR}}DescripcionOperacion");
            if ($descriptionElement !== null) {
                $item->description = $descriptionElement->asText();
            }

            // Parse hash
            $hashElement = $recordElement->get("{{$nsConR}}Huella");
            if ($hashElement !== null) {
                $item->hash = $hashElement->asText();
            }

            // Parse hashed at timestamp
            $hashedAtElement = $recordElement->get("{{$nsConR}}FechaHoraHusoGenRegistro");
            if ($hashedAtElement !== null) {
                $hashedAt = DateTimeImmutable::createFromFormat(DateTimeInterface::ISO8601, $hashedAtElement->asText());
                if ($hashedAt === false) {
                    throw new AeatException('Invalid hashed at date: ' . $hashedAtElement->asText());
                }
                $item->hashedAt = $hashedAt;
            }

            // Parse registration status
            $statusElement = $recordElement->get("{{$nsConR}}EstadoRegistro");
            if ($statusElement !== null) {
                $item->registrationStatus = $statusElement->asText();
            }

            $instance->items[] = $item;
        }

        return $instance;
    }

    /**
     * Whether there are more pages of results available
     *
     * @field IndicadorPaginacion
     */
    public bool $hasMorePages = false;

    /**
     * Clave de paginación para obtener la siguiente página de resultados
     *
     * Pass this value as {@see InvoiceQuery::$paginationId} in the next call to {@see AeatClient::query()}
     * to retrieve the next page of results.
     *
     * @field ClavePaginacion
     */
    public ?InvoiceIdentifier $nextPaginationId = null;

    /**
     * Registros de facturación que coinciden con los filtros de la consulta
     *
     * @var ConsultaResponseItem[]
     *
     * @field RegistroRespuestaConsultaFactu
     */
    public array $items = [];
}
