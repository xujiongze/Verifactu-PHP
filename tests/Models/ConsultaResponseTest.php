<?php
namespace josemmo\Verifactu\Tests\Models;

use josemmo\Verifactu\Exceptions\AeatException;
use josemmo\Verifactu\Models\Records\InvoiceType;
use josemmo\Verifactu\Models\Responses\ConsultaResponse;
use PHPUnit\Framework\TestCase;
use UXML\UXML;

final class ConsultaResponseTest extends TestCase {
    public function testParsesResponseWithMultipleRecords(): void {
        $xml = UXML::fromString(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:conR="https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/RespuestaConsultaLR.xsd" xmlns:tik="https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd">
            <env:Header/>
            <env:Body>
                <conR:RespuestaConsultaFactuSistemaFacturacion>
                    <conR:IndicadorPaginacion>S</conR:IndicadorPaginacion>
                    <conR:ClavePaginacion>
                        <tik:IDEmisorFactura>A00000000</tik:IDEmisorFactura>
                        <tik:NumSerieFactura>TEST-202510-124</tik:NumSerieFactura>
                        <tik:FechaExpedicionFactura>13-10-2025</tik:FechaExpedicionFactura>
                    </conR:ClavePaginacion>
                    <conR:RegistroRespuestaConsultaFactu>
                        <conR:IDFactura>
                            <tik:IDEmisorFactura>A00000000</tik:IDEmisorFactura>
                            <tik:NumSerieFactura>TEST-202510-123</tik:NumSerieFactura>
                            <tik:FechaExpedicionFactura>13-10-2025</tik:FechaExpedicionFactura>
                        </conR:IDFactura>
                        <conR:NombreRazonEmisor>Perico de los Palotes, S.A.</conR:NombreRazonEmisor>
                        <conR:TipoFactura>F1</conR:TipoFactura>
                        <conR:DescripcionOperacion>Factura de prueba</conR:DescripcionOperacion>
                        <conR:Huella>AABBCCDDAABBCCDDAABBCCDDAABBCCDDAABBCCDDAABBCCDDAABBCCDDAABBCCDD</conR:Huella>
                        <conR:FechaHoraHusoGenRegistro>2025-10-13T10:00:00+02:00</conR:FechaHoraHusoGenRegistro>
                        <conR:EstadoRegistro>Correcto</conR:EstadoRegistro>
                    </conR:RegistroRespuestaConsultaFactu>
                    <conR:RegistroRespuestaConsultaFactu>
                        <conR:IDFactura>
                            <tik:IDEmisorFactura>A00000000</tik:IDEmisorFactura>
                            <tik:NumSerieFactura>TEST-202510-124</tik:NumSerieFactura>
                            <tik:FechaExpedicionFactura>13-10-2025</tik:FechaExpedicionFactura>
                        </conR:IDFactura>
                        <conR:NombreRazonEmisor>Perico de los Palotes, S.A.</conR:NombreRazonEmisor>
                        <conR:TipoFactura>F2</conR:TipoFactura>
                        <conR:DescripcionOperacion>Factura simplificada</conR:DescripcionOperacion>
                        <conR:Huella>1122334411223344112233441122334411223344112233441122334411223344</conR:Huella>
                        <conR:FechaHoraHusoGenRegistro>2025-10-13T11:00:00+02:00</conR:FechaHoraHusoGenRegistro>
                        <conR:EstadoRegistro>Correcto</conR:EstadoRegistro>
                    </conR:RegistroRespuestaConsultaFactu>
                </conR:RespuestaConsultaFactuSistemaFacturacion>
            </env:Body>
        </env:Envelope>
        XML);
        $response = ConsultaResponse::from($xml);

        $this->assertTrue($response->hasMorePages);

        $this->assertNotNull($response->nextPaginationId);
        $this->assertEquals('A00000000', $response->nextPaginationId->issuerId);
        $this->assertEquals('TEST-202510-124', $response->nextPaginationId->invoiceNumber);
        $this->assertEquals('2025-10-13 00:00:00', $response->nextPaginationId->issueDate->format('Y-m-d H:i:s'));

        $this->assertCount(2, $response->items);

        $this->assertEquals('A00000000', $response->items[0]->invoiceId->issuerId);
        $this->assertEquals('TEST-202510-123', $response->items[0]->invoiceId->invoiceNumber);
        $this->assertEquals('2025-10-13 00:00:00', $response->items[0]->invoiceId->issueDate->format('Y-m-d H:i:s'));
        $this->assertEquals('Perico de los Palotes, S.A.', $response->items[0]->issuerName);
        $this->assertEquals(InvoiceType::Factura, $response->items[0]->invoiceType);
        $this->assertEquals('Factura de prueba', $response->items[0]->description);
        $this->assertEquals('AABBCCDDAABBCCDDAABBCCDDAABBCCDDAABBCCDDAABBCCDDAABBCCDDAABBCCDD', $response->items[0]->hash);
        $this->assertEquals('2025-10-13T10:00:00+02:00', $response->items[0]->hashedAt->format('Y-m-d\TH:i:sP'));
        $this->assertEquals('Correcto', $response->items[0]->registrationStatus);

        $this->assertEquals('TEST-202510-124', $response->items[1]->invoiceId->invoiceNumber);
        $this->assertEquals(InvoiceType::Simplificada, $response->items[1]->invoiceType);
        $this->assertEquals('Factura simplificada', $response->items[1]->description);
    }

    public function testParsesEmptyResponse(): void {
        $xml = UXML::fromString(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:conR="https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/RespuestaConsultaLR.xsd">
            <env:Header/>
            <env:Body>
                <conR:RespuestaConsultaFactuSistemaFacturacion>
                    <conR:IndicadorPaginacion>N</conR:IndicadorPaginacion>
                </conR:RespuestaConsultaFactuSistemaFacturacion>
            </env:Body>
        </env:Envelope>
        XML);
        $response = ConsultaResponse::from($xml);

        $this->assertFalse($response->hasMorePages);
        $this->assertNull($response->nextPaginationId);
        $this->assertCount(0, $response->items);
    }

    public function testHandlesServerErrors(): void {
        $xml = UXML::fromString(<<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/">
            <env:Body>
                <env:Fault>
                    <faultcode>env:Server</faultcode>
                    <faultstring>Codigo[20009].Error interno en el servidor</faultstring>
                </env:Fault>
            </env:Body>
        </env:Envelope>
        XML);
        try {
            ConsultaResponse::from($xml);
            $this->fail('Did not throw exception for server error response');
        } catch (AeatException $e) {
            $this->assertStringContainsString('Codigo[20009].Error interno en el servidor', $e->getMessage());
        }
    }

    public function testThrowsExceptionForUnexpectedXmlResponse(): void {
        $this->expectException(AeatException::class);
        $this->expectExceptionMessage('Missing <conR:RespuestaConsultaFactuSistemaFacturacion /> element from response');
        $xml = UXML::fromString('<env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/"><env:Body/></env:Envelope>');
        ConsultaResponse::from($xml);
    }
}
