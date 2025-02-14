<?php
require 'vendor/autoload.php';
require_once 'tcpdf/TCPDF-main/tcpdf.php'; 
use PhpOffice\PhpSpreadsheet\IOFactory;
session_start();

$localhost = "localhost";
$user = "root";
$pass = "";
$dbname = "todo";
$connect = mysqli_connect($localhost, $user, $pass, $dbname);

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}


// Directory where files will be uploaded
$targetDir = "uploads/";

// Check if directory exists, if not create it
if (!file_exists($targetDir)) {
    if (!mkdir($targetDir, 0777, true)) {
        die("Failed to create directory.");
    }
} 

// Insert Task
if (isset($_POST["addTask"])) {
    $Task = $_POST["taskInput"];
    $TaskDate = date("Y-m-d", strtotime($_POST["taskDate"]));
    $TaskTime = date("H:i:s", strtotime($_POST["taskTime"]));
    $Priority = $_POST["priority"];
    $Category = $_POST["category"];

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $fileName = basename($_FILES["attachment"]["name"]);
        $fileTmpPath = $_FILES["attachment"]["tmp_name"];
        $targetDir = "uploads/";
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $fileSize = $_FILES["attachment"]["size"];

        // Allowed file types
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx','webp'];

        // Check if the file type is allowed
        if (in_array($fileType, $allowedTypes)) {
            // Move the uploaded file to the target directory
            if (move_uploaded_file($fileTmpPath, $targetFilePath)) {
                // Insert task details into the database
                $sql = "INSERT INTO todos (task_name, task_date, task_time, priority, category, attachment, file_type, file_size) 
                        VALUES ('$Task', '$TaskDate', '$TaskTime', '$Priority', '$Category', '$targetFilePath', '$fileType', '$fileSize')";
                
                $result = mysqli_query($connect, $sql);

                if ($result) {
                    // Redirect to Task page after successful insertion
                    header("Location: Task.php");
                    exit();
                } else {
                    echo "Error inserting task: " . mysqli_error($connect);
                }
            } else {
                echo "Error uploading file.";
            }
        } else {
            echo "Invalid file type. Allowed types: jpg, jpeg, png, gif, pdf, doc, docx.";
        }
    } else {
        echo "No file uploaded or error occurred.";
    }
}




// Fetching All Data
function getallTasks($connect) {
    $FetchData = "SELECT * FROM todos ORDER BY id DESC"; // Removed LIMIT & OFFSET

    // Execute the query
    $RetrivedData = mysqli_query($connect, $FetchData);

    // Check if the query was successful
    if ($RetrivedData) {
        $Data = [];
        while ($row = mysqli_fetch_assoc($RetrivedData)) {
            $Data[] = $row;
        }
        echo json_encode(['Data' => $Data]);
    } else {
        echo json_encode(['error' => 'Error fetching tasks']);
    }
}
    







//Update Task
if (isset($_GET['complete_id'])) {
    $taskId = $_GET['complete_id'];

    $sql = "UPDATE todos SET status = 'completed' WHERE id = $taskId";
    mysqli_query($connect, $sql);
    
    header("Location: Task.php");
    exit();
}

//Delete Task

if (isset($_GET['delete_id'])) {
    $taskId = $_GET['delete_id'];
    $sql = "DELETE FROM todos WHERE id = $taskId";
    mysqli_query($connect, $sql);
    header("Location: Task.php");
    exit();
}


// edit task
function editTask($connect, $inputData) {
    $taskId = $inputData['task_id'] ?? null;
    $taskName = $inputData['task_name'] ?? '';
    $taskDate = date("Y-m-d", strtotime($inputData['task_date'] ?? ''));
    $taskTime = date("H:i:s", strtotime($inputData['task_time'] ?? ''));
    $priority = $inputData['priority'] ?? '';
    $category = $inputData['category'] ?? '';
    $status = $inputData['status'] ?? '';

    // Check if required fields are filled
    if (!$taskId || !$taskName || !$taskDate || !$taskTime || !$priority || !$category || !$status) {
        return ["error" => "All fields are required."];
    }

    // SQL Query (Without Prepared Statements)
    $sql = "UPDATE todos SET 
            task_name = '$taskName', 
            task_date = '$taskDate', 
            task_time = '$taskTime', 
            priority = '$priority', 
            category = '$category', 
            status = '$status'  
            WHERE id = $taskId";

    if (mysqli_query($connect, $sql)) {
        return ["message" => "Task updated successfully."];
        header("Location: Task.php");
        exit();
    } else {
        return ["error" => "Failed to update task: " . mysqli_error($connect)];
    }
}

//Export to csv file

if (isset($_POST['csvTask'])) {
    $FetchData = "SELECT id, task_name, status, task_date, priority, task_time, category, attachment, file_type, file_size 
                  FROM todos ORDER BY id ASC";
    $result = mysqli_query($connect, $FetchData);

    if ($result) {
        // Generate the filename with the current date
        $filename = "tasks_" . date('Ymd') . ".csv";

        // Clear output buffer to prevent unexpected content
        ob_clean();

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Open output stream to write CSV
        $output = fopen('php://output', 'w');
        
        // Add column headers (including new fields)
        fputcsv($output, ['ID', 'Task Name', 'Status', 'Task Date', 'Priority', 'Task Time', 'Category', 'Attachment', 'File Type', 'File Size']);

        // Fetch and write data to CSV
        while ($row = mysqli_fetch_assoc($result)) {
            // Format task_time to "h:i A"
            $row['task_time'] = date("h:i A", strtotime($row['task_time']));
            
            // Write each row to the CSV file, including new fields
            fputcsv($output, [
                $row['id'], 
                $row['task_name'], 
                $row['status'], 
                $row['task_date'], 
                $row['priority'], 
                $row['task_time'], 
                $row['category'], 
                $row['attachment'], 
                $row['file_type'], 
                $row['file_size']
            ]);
        }

        // Close the output stream and exit
        fclose($output);
        exit;
    } else {
        echo "Error fetching data";
    }
}




//export to pdf


if(isset($_POST['pdfTask'])){
    $FetchData = "SELECT id, task_name, status,task_date,priority,task_time,category, attachment, file_type, file_size FROM todos ORDER BY id ASC";
    $result = mysqli_query($connect, $FetchData);

    // Create new PDF document with A3 page size in landscape mode
    $pdf = new TCPDF('L', 'mm', 'A3', true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Task Manager');
    $pdf->SetTitle('Task List');
    $pdf->SetHeaderData('', '', 'Task Manager', 'Generated on ' . date('Y-m-d'));
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->AddPage();

    // Set Title
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 15, 'Task List', 0, 1, 'C');
    $pdf->Ln(5);

    // Set table headers
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(15, 12, 'ID', 1);
    $pdf->Cell(60, 12, 'Task Name', 1);
    $pdf->Cell(25, 12, 'Status', 1);
    $pdf->Cell(35, 12, 'Task Date', 1);
    $pdf->Cell(25, 12, 'Priority', 1);
    $pdf->Cell(35, 12, 'Task Time', 1);
    $pdf->Cell(35, 12, 'Category', 1);
    $pdf->Cell(40, 12, 'Attachment', 1);
    $pdf->Cell(25, 12, 'File Type', 1);
    $pdf->Cell(25, 12, 'Size (KB)', 1);
    $pdf->Ln(); // Move to the next line

    // Fetch and write task data
    $pdf->SetFont('helvetica', '', 12);
    while ($row = $result->fetch_assoc()) {
        // Format date and time before writing to PDF
        $formattedDate = date("d-m-Y", strtotime($row['task_date'])); 
        $formattedTime = date("h:i A", strtotime($row['task_time']));
        $formattedSize = number_format($row['file_size'] / 1024, 2); // Convert bytes to KB

        $pdf->Cell(15, 12, (int)$row['id'], 1);
        $pdf->Cell(60, 12, $row['task_name'], 1);
        $pdf->Cell(25, 12, $row['status'], 1);
        $pdf->Cell(35, 12, $formattedDate, 1);
        $pdf->Cell(25, 12, $row['priority'], 1);
        $pdf->Cell(35, 12, $formattedTime, 1);
        $pdf->Cell(35, 12, $row['category'], 1);
        $pdf->Cell(40, 12, basename($row['attachment']), 1); // Show only filename
        $pdf->Cell(25, 12, $row['file_type'], 1);
        $pdf->Cell(25, 12, $formattedSize, 1);
        $pdf->Ln(); // Move to next row
    }

    // Ensure no extra output
    ob_end_clean();

    // Output PDF
    $pdf->Output('tasks.pdf', 'D');
    exit();
}


//import csv file

if (isset($_POST['import'])) {
    if (!isset($_FILES['xlsx_file']) || $_FILES['xlsx_file']['error'] !== UPLOAD_ERR_OK) {
        die("Error: No file uploaded or an error occurred!");
    }

    $filePath = $_FILES['xlsx_file']['tmp_name'];
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Skip the first row (header)
    array_shift($rows);

    // Paths
    $sourceDir = "C:/Users/Ashiq/OneDrive/Desktop/uploads/"; // Path where Excel's referenced files exist
    $targetDir = "uploads/"; // Destination inside your project

    // Create the target uploads directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    foreach ($rows as $row) {
        $task_name = mysqli_real_escape_string($connect, $row[0]);
        $status = mysqli_real_escape_string($connect, $row[1]);
        $task_date = mysqli_real_escape_string($connect, $row[2]);
        $priority = mysqli_real_escape_string($connect, $row[3]);
        $task_time = mysqli_real_escape_string($connect, $row[4]);
        $category = mysqli_real_escape_string($connect, $row[5]);
        $attachment = trim($row[6]); // File path from Excel

        $fileName = ""; // Default empty if no file

        if (!empty($attachment)) {
            $fileName = basename($attachment); // Extract filename (e.g., React.jpg)
            $sourceFile = $sourceDir . $fileName;
            $newFilePath = $targetDir . $fileName;

            // Check if the file exists in the source directory
            if (file_exists($sourceFile)) {
                if (copy($sourceFile, $newFilePath)) {
                    echo "File copied successfully: $fileName <br>";
                    $fileName = $newFilePath; // Store full path (uploads/React.jpg) in the database
                } else {
                    echo "Failed to copy file: $fileName <br>";
                    $fileName = ""; // Set empty if copy fails
                }
            } else {
                echo "File not found: $sourceFile <br>";
                $fileName = ""; // Set empty if missing
            }
        }

        // Ensure correct date format
        if (!empty($task_date) && strtotime($task_date) !== false) {
            $task_date = date("Y-m-d", strtotime($task_date));
        } else {
            $task_date = NULL; // Store NULL if invalid
        }

        // Store the correct file path in the database
        $sql = "INSERT INTO todos (task_name, status, task_date, priority, task_time, category, attachment) 
                VALUES ('$task_name', '$status', '$task_date', '$priority', '$task_time', '$category', '$fileName')";

        if (!mysqli_query($connect, $sql)) {
            echo "Error: " . mysqli_error($connect) . "<br>";
        }
    }

    echo "XLSX data imported successfully!";
}


//Fetching Data To Calculate Task Percentage

function CalculateTaskPercentage($connect){

$queryTotal = "SELECT COUNT(*) AS total FROM todos";
$queryCompleted = "SELECT COUNT(*) AS completed FROM todos WHERE status = 'completed'";

$resultTotal = mysqli_query($connect, $queryTotal);
$resultCompleted = mysqli_query($connect, $queryCompleted);

$totalTasks = mysqli_fetch_assoc($resultTotal)['total'];
$completedTasks = mysqli_fetch_assoc($resultCompleted)['completed'];

$percentage = ($totalTasks > 0) ? round(($completedTasks / $totalTasks) * 100, 2) : 0;

return[
    'totalTasks' => $totalTasks,
    'completedTasks' => $completedTasks,
    'percentage' => $percentage
];

}
//Sort Task

function SortTask($connect,$sortCategory){

if ($sortCategory) {
    $sql = "SELECT * FROM todos WHERE category = '$sortCategory' ORDER BY id DESC";
}
else{
    $sql = "SELECT * FROM todos ORDER BY id DESC";
}
// Execute the query
$result = mysqli_query($connect, $sql);

if (!$result) {
    die("Error happen while Sorting Data");
    }
$Data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $Data[] = $row;
}
return ['Data' => $Data];


}

//Search Task
function searchTask($connect, $searchTask,$sortCategory){

    $sql = "SELECT * FROM todos WHERE 1";


    if ($searchTask) {
        $sql .= " AND task_name LIKE '%$searchTask%'";
    }
    if ($sortCategory) {
        $sql .= " AND category = '$sortCategory'";
    }
    

    $Result = mysqli_query($connect, $sql);

        // Check if the query was successful
        if (!$Result) {
            die("Error happened while searching data: " . mysqli_error($connect));
        }

$Data = [];
while ($row = mysqli_fetch_assoc($Result)) {
    $Data[] = $row;
}
return ['Data' => $Data];
}
//auto_delete
function auto_delete($connect) {
    $deleteQuery = "DELETE FROM todos WHERE task_date <= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $result = mysqli_query($connect, $deleteQuery);
    if ($result) {
        return ["success" => true, "message" => "Completed tasks older than 7 days deleted."]; // Return an array
    } else {
        return ["success" => false, "message" => "Failed to delete tasks."]; // Return an array
    }

}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputData = json_decode(file_get_contents("php://input"), true);
    
    if ($inputData['action'] === 'sortCategory') {
        $sortCategory = $inputData['sortCategory'] ?? ''; // Default to empty string
        echo json_encode(SortTask($connect, $sortCategory));
    }
    if ($inputData['action'] === 'search') {
        $searchTask=$inputData['searchInput'] ?? '';
        $sortCategory = $inputData['sortCategory'] ?? '';
        echo json_encode(searchTask($connect, $searchTask, $sortCategory));
    }
    if ($inputData['action'] === 'updateTask') { 
        echo json_encode(editTask($connect, $inputData));
    }
    if ($inputData['action'] === 'auto_delete') {
        echo json_encode(auto_delete($connect));
    }

    


} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    if ($_GET['action'] === 'percentage') {
        echo json_encode(calculateTaskPercentage($connect));
    }
}
else{
    getAllTasks($connect);
}



mysqli_close($connect);




?>