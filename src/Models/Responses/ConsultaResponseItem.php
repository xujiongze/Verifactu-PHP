<?php
namespace josemmo\Verifactu\Models\Responses;

use DateTimeImmutable;
use josemmo\Verifactu\Models\Model;
use josemmo\Verifactu\Models\Records\InvoiceIdentifier;
use josemmo\Verifactu\Models\Records\InvoiceType;

/**
 * Individual invoice record returned by a ConsultaLR query
 *
 * @field RegistroRespuestaConsultaFactu
 */
class ConsultaResponseItem extends Model {
    /**
     * ID de factura
     *
     * @field IDFactura
     */
    public InvoiceIdentifier $invoiceId;

    /**
     * Nombre-razón social del emisor
     *
     * @field NombreRazonEmisor
     */
    public string $issuerName;

    /**
     * Tipo de factura
     *
     * @field TipoFactura
     */
    public InvoiceType $invoiceType;

    /**
     * Descripción de la operación
     *
     * @field DescripcionOperacion
     */
    public string $description;

    /**
     * Huella o hash del registro de facturación
     *
     * @field Huella
     */
    public string $hash;

    /**
     * Fecha, hora y huso horario de generación del registro de facturación
     *
     * @field FechaHoraHusoGenRegistro
     */
    public DateTimeImmutable $hashedAt;

    /**
     * Estado del registro en el sistema de la AEAT
     *
     * Possible values include "Correcto", "AceptadoConErrores", "Incorrecto" and "Anulado".
     *
     * @field EstadoRegistro
     */
    public string $registrationStatus;
}
