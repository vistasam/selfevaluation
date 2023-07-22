<?php

require_once 'alt_autoload.php-dist';

// Initialize and load PDF Parser library 
$parser = new \Smalot\PdfParser\Parser(); 
 
// Source PDF file to extract text 
$file = 'digitABC.pdf';
 
// Parse pdf file using Parser library 
$pdf = $parser->parseFile($file); 

// Extract all pages from the PDF
$pages = $pdf->getPages();

// Array to store competence information
$competences = [];

// Iterate through each page and extract the competence information
foreach ($pages as $page) {
    // Extract text from the current page
    $text = $page->getText();

    // Use regular expressions to extract the competence information
    if (preg_match_all('/Competence area (\d+)\. (.+):\s+Self-assessment questions: (\d+)% \((.+)\)/', $text, $matches, PREG_SET_ORDER)) {
        // First type of PDF format
        foreach ($matches as $match) {
            $competenceNumber = $match[1];
            $competenceName = $match[2];
            $percentage = $match[3];
            $level = $match[4];

            $competences[$competenceNumber] = [
                'name' => $competenceName,
                'percentage' => $percentage,
                'level' => $level,
            ];
        }
    } elseif (preg_match_all('/Competence area (\d+)\. (.+):\s+Knowledge-based questions: (\d+)% \((.+)\)/', $text, $matches, PREG_SET_ORDER)) {
        // Second type of PDF format
        foreach ($matches as $match) {
            $competenceNumber = $match[1];
            $competenceName = $match[2];
            $percentage = $match[3];
            $level = $match[4];

            $competences[$competenceNumber] = [
                'name' => $competenceName,
                'percentage' => $percentage,
                'level' => $level,
            ];
        }
    }
}

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pdf";

// Establish database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Iterate through each page and extract the competence information
foreach ($competences as $competenceNumber => $competence) {
    $competenceName = $competence['name'];
    $percentage = $competence['percentage'];
    $level = $competence['level'];

    // Prepare and execute the database insert statement
    $stmt = $conn->prepare("INSERT INTO competences (competence_number, competence_name, percentage, level) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $competenceNumber, $competenceName, $percentage, $level);
    $stmt->execute();
}

// Close the database connection
$conn->close();

// Output the competence information
foreach ($competences as $competenceNumber => $competence) {
    echo "Competence area {$competenceNumber}: {$competence['name']}\n";
    echo "Percentage: {$competence['percentage']}%\n";
    echo "Level: {$competence['level']}\n";
    echo "\n";
}
?>
