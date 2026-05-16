<?php
namespace josemmo\Verifactu\Models\Queries;

use DateTimeImmutable;
use josemmo\Verifactu\Models\Model;
use josemmo\Verifactu\Models\Records\InvoiceIdentifier;
use josemmo\Verifactu\Models\Records\InvoiceType;
use josemmo\Verifactu\Services\AeatClient;
use Symfony\Component\Validator\Constraints as Assert;
use UXML\UXML;

/**
 * Filter for consulting invoices issued via VERI*FACTU
 *
 * @field FiltroConsulta
 */
class InvoiceQuery extends Model {
    /**
     * ID de factura concreto a consultar
     *
     * @field IDFactura
     */
    #[Assert\Valid]
    public ?InvoiceIdentifier $invoiceId = null;

    /**
     * Fecha de expedición desde (inclusive)
     *
     * NOTE: Time part will be ignored.
     *
     * @field FechaExpedicionDesde
     */
    public ?DateTimeImmutable $issueDateFrom = null;

    /**
     * Fecha de expedición hasta (inclusive)
     *
     * NOTE: Time part will be ignored.
     *
     * @field FechaExpedicionHasta
     */
    public ?DateTimeImmutable $issueDateTo = null;

    /**
     * NIF del destinatario de la factura
     *
     * @field NIFDestinatario
     */
    #[Assert\Length(max: 9)]
    public ?string $recipientNif = null;

    /**
     * Nombre o razón social del destinatario de la factura
     *
     * @field NombreRazonDestinatario
     */
    #[Assert\Length(max: 120)]
    public ?string $recipientName = null;

    /**
     * Tipo de factura
     *
     * @field TipoFactura
     */
    public ?InvoiceType $invoiceType = null;

    /**
     * Clave de paginación: último ID de factura recibido en la consulta anterior
     *
     * Used to retrieve the next page of results. Set to the last invoice ID returned
     * in the previous call to {@see AeatClient::query()}.
     *
     * @field ClavePaginacion
     */
    #[Assert\Valid]
    public ?InvoiceIdentifier $paginationId = null;

    /**
     * Export filter to XML
     *
     * @param UXML   $xml         XML parent element
     * @param string $conNsPrefix XML namespace prefix for ConsultaLR elements (e.g. "con")
     * @param string $tikNsPrefix XML namespace prefix for SuministroInformacion elements (e.g. "sum1")
     */
    public function export(UXML $xml, string $conNsPrefix, string $tikNsPrefix): void {
        $filterElement = $xml->add("$conNsPrefix:FiltroConsulta");

        if ($this->invoiceId !== null) {
            $idFacturaElement = $filterElement->add("$conNsPrefix:IDFactura");
            $idFacturaElement->add("$tikNsPrefix:IDEmisorFactura", $this->invoiceId->issuerId);
            $idFacturaElement->add("$tikNsPrefix:NumSerieFactura", $this->invoiceId->invoiceNumber);
            $idFacturaElement->add("$tikNsPrefix:FechaExpedicionFactura", $this->invoiceId->issueDate->format('d-m-Y'));
        }
        if ($this->issueDateFrom !== null) {
            $filterElement->add("$conNsPrefix:FechaExpedicionDesde", $this->issueDateFrom->format('d-m-Y'));
        }
        if ($this->issueDateTo !== null) {
            $filterElement->add("$conNsPrefix:FechaExpedicionHasta", $this->issueDateTo->format('d-m-Y'));
        }
        if ($this->recipientNif !== null) {
            $filterElement->add("$conNsPrefix:NIFDestinatario", $this->recipientNif);
        }
        if ($this->recipientName !== null) {
            $filterElement->add("$conNsPrefix:NombreRazonDestinatario", $this->recipientName);
        }
        if ($this->invoiceType !== null) {
            $filterElement->add("$conNsPrefix:TipoFactura", $this->invoiceType->value);
        }
        if ($this->paginationId !== null) {
            $clavePaginacionElement = $filterElement->add("$conNsPrefix:ClavePaginacion");
            $clavePaginacionElement->add("$tikNsPrefix:IDEmisorFactura", $this->paginationId->issuerId);
            $clavePaginacionElement->add("$tikNsPrefix:NumSerieFactura", $this->paginationId->invoiceNumber);
            $clavePaginacionElement->add("$tikNsPrefix:FechaExpedicionFactura", $this->paginationId->issueDate->format('d-m-Y'));
        }
    }
}
