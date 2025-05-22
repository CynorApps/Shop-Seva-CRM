<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

// Include database connection
require_once 'db.php';
require_once 'tcpdf/tcpdf.php';

// Get sale ID
$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;

if ($sale_id <= 0) {
    die("Invalid sale ID.");
}

// Fetch sale details
$sql_sale = "SELECT s.customer_name, s.customer_phone, s.total, s.discount, s.created_at 
             FROM sales s 
             WHERE s.id = ?";
$stmt_sale = $conn->prepare($sql_sale);
$stmt_sale->bind_param("i", $sale_id);
$stmt_sale->execute();
$sale = $stmt_sale->get_result()->fetch_assoc();
$stmt_sale->close();

if (!$sale) {
    die("Sale not found.");
}

// Fetch sale items
$sql_items = "SELECT p.name, si.quantity, si.price 
              FROM sale_items si 
              JOIN products p ON si.product_id = p.id 
              WHERE si.sale_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $sale_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$stmt_items->close();

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Shop-Seva');
$pdf->SetTitle('Invoice');
$pdf->SetSubject('Customer Invoice');
$pdf->SetKeywords('Invoice, Shop-Seva, Bill');

// Set default header data
$pdf->SetHeaderData('', 0, 'Shop-Seva Invoice', "Date: " . date('d-m-Y', strtotime($sale['created_at'])));

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Shop Name
$pdf->Cell(0, 10, 'Shop-Seva', 0, 1, 'C');
$pdf->Ln(5);

// Product Table
$html = '<table border="1" cellpadding="5">
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price (₹)</th>
                <th>Subtotal (₹)</th>
            </tr>';
$subtotal = 0;
while ($item = $result_items->fetch_assoc()) {
    $item_subtotal = $item['quantity'] * $item['price'];
    $subtotal += $item_subtotal;
    $html .= "<tr>
                <td>" . htmlspecialchars($item['name']) . "</td>
                <td>" . $item['quantity'] . "</td>
                <td>" . number_format($item['price'], 2) . "</td>
                <td>" . number_format($item_subtotal, 2) . "</td>
              </tr>";
}
$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

// Totals
$pdf->Ln(5);
$pdf->Cell(0, 10, "Subtotal: ₹" . number_format($subtotal, 2), 0, 1);
if ($sale['discount'] > 0) {
    $discount_amount = ($sale['discount'] / 100) * $subtotal;
    $pdf->Cell(0, 10, "Discount ({$sale['discount']}%): -₹" . number_format($discount_amount, 2), 0, 1);
}
$pdf->Cell(0, 10, "Total: ₹" . number_format($sale['total'], 2), 0, 1);

// Customer Details
$pdf->Ln(10);
$pdf->Cell(0, 10, 'Customer Details:', 0, 1);
$pdf->Cell(0, 10, "Name: " . htmlspecialchars($sale['customer_name']), 0, 1);
if (!empty($sale['customer_phone'])) {
    $pdf->Cell(0, 10, "Phone: " . htmlspecialchars($sale['customer_phone']), 0, 1);
}

// Output the PDF
$pdf->Output("invoice_$sale_id.pdf", 'I');

$conn->close();
?>