<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;

class FacturaService
{
    /**
     * Genera un PDF de la factura
     *
     * @param array $pedido Datos del pedido
     * @param array $productos Lista de productos
     * @param string $rutaFactura Ruta donde se guardará el PDF
     */
    public function generarFacturaPDF(array $pedido, array $productos, string $rutaFactura): void
    {
        $html = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body {
                    font-family: 'Helvetica', Arial, sans-serif;
                    font-size: 12px;
                    color: #333;
                    margin: 0;
                    padding: 30px;
                }
                .container {
                    max-width: 800px;
                    margin: 0 auto;
                    border: 1px solid #eee;
                    padding: 30px;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
                    background: #fff;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #e0e0e0;
                }
                .header h1 {
                    color: #28a745;
                    font-size: 28px;
                    margin-bottom: 5px;
                    text-transform: uppercase;
                }
                .header p {
                    font-size: 14px;
                    color: #555;
                    line-height: 1.6;
                }
                .section-title {
                    font-size: 16px;
                    color: #28a745;
                    margin-top: 25px;
                    margin-bottom: 10px;
                    padding-bottom: 5px;
                    border-bottom: 1px solid #eee;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 12px 10px;
                    text-align: left;
                    font-size: 13px;
                }
                th {
                    background-color: #f8f8f8;
                    color: #444;
                    font-weight: bold;
                    text-transform: uppercase;
                }
                tbody tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .totales td {
                    font-weight: bold;
                    padding-top: 15px;
                    padding-bottom: 15px;
                    font-size: 14px;
                }
                .totales tr:last-child td {
                    background-color: #e6ffe6;
                    color: #198754;
                    font-size: 16px;
                    border-top: 2px solid #28a745;
                }
                .payment-info {
                    margin-top: 30px;
                    font-size: 13px;
                    color: #555;
                }
                .footer {
                    margin-top: 40px;
                    text-align: center;
                    font-size: 11px;
                    color: #999;
                    padding-top: 15px;
                    border-top: 1px solid #eee;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Factura N° {$pedido['numero_factura']}</h1>
                    <p><strong>Fecha:</strong> {$pedido['fecha']}</p>
                </div>

                <div class='section-title'>Detalles del Cliente</div>
                <p>
                    <strong>Nombre:</strong> {$pedido['nombre_cliente']}<br>
                    <strong>Email:</strong> {$pedido['email']}
                </p>

                <div class='section-title'>Productos Comprados</div>
                <table>
                    <thead>
                        <tr><th>Producto</th><th>Cantidad</th><th>Precio Unitario</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>";
        
        foreach ($productos as $prod) {
            $subtotal = $prod['precio'] * $prod['cantidad'];
            $html .= "<tr>
                <td>{$prod['nombre']}</td>
                <td>{$prod['cantidad']}</td>
                <td>&#8353;" . number_format($prod['precio'], 2) . "</td>
                <td>&#8353;" . number_format($subtotal, 2) . "</td>
            </tr>";
        }

        $html .= "
                    </tbody>
                </table>
                <table style='margin-top:20px; width:100%;'>
                    <tr class='totales'><td colspan='3' style='text-align:right;'>Subtotal</td><td>&#8353;" . number_format($pedido['subtotal'], 2) . "</td></tr>
                    <tr class='totales'><td colspan='3' style='text-align:right;'>Descuento</td><td>&#8353;" . number_format($pedido['descuento'], 2) . "</td></tr>
                    <tr class='totales'><td colspan='3' style='text-align:right;'>Total a Pagar</td><td>&#8353;" . number_format($pedido['total'], 2) . "</td></tr>
                </table>

                <div class='payment-info'>
                    <strong>Método de pago:</strong> {$pedido['metodo_pago']}
                </div>

                <div class='footer'>
                    Gracias por tu compra en EcoMariposa.<br>
                    © " . date('Y') . " EcoMariposa. Todos los derechos reservados.
                </div>
            </div>
        </body>
        </html>";

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($rutaFactura, $dompdf->output());
    }
}
