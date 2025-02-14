
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo List</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="Todoapp.css">
     
      <!-- Load Flatpickr JS with defer -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="Functions.js" defer></script> <!-- Your custom JS file --> 

  </head>
<body>
  <div class="container">
      <!-- Buttons To Export the Task as csv and pdf -->
      <div class="header">
    <form action="Database.php" method="post">
        <button type="submit" name="csvTask">Export to CSV</button>
    </form>
    <form action="Database.php" method="post">
        <button type="submit" name="pdfTask">Export to PDF</button>
    </form>
      </div> 

   
    <!-- Form for adding a new task -->
    <form action="Database.php" method="post"  enctype="multipart/form-data">
      <h1>Todo List</h1>
      <input type="text" id="taskInput" placeholder="Enter a task" name="taskInput" >
      <input type="date" id="inputdate" name="taskDate" placeholder="Select a due date">
      <input type="time" id="taskTime" name="taskTime"  placeholder="Select a due time">
      <div class="priority">
        <label>Priority Level:</label><br>
        
     
        <input type="radio" id="low" name="priority" value="Low" > 
        <label for="low" class="priority-label">Low</label><br>

        <input type="radio" id="medium" name="priority" value="Medium">
        <label for="medium" class="priority-label">Medium</label><br>

        <input type="radio" id="high" name="priority" value="High">
        <label for="high" class="priority-label">High</label>
      </div>

      <div>
      <div class="category">
        <label for="category">Category:</label><br>
        <select name="category" id="category" required>
          <option value="Work">Work</option>
          <option value="Personal">Personal</option>
        </select>
      </div>
      <!-- Upload Task Document -->
              <div id="upload-task">
                <p>Attach an image or PDF</p>
              <button type="button" id="uploadBtn">Upload File</button>
              <input type="file" id="attachment" name="attachment" style="display: none;">
              </div>
<!-- Add task -->
      <div>
      <button type="submit" name="addTask">Add Task</button>
      </div>
    </form>

    <!-- <!-- CSV Upload Form -->
    <div id="upload-csv">
      <p>Import tasks from Excel Sheet.</p>
    <form action="Database.php" method="post" enctype="multipart/form-data">
        <input type="file" id="file" name="xlsx_file" accept=".xlsx" required style="display: none;">
        <label for="file" id="file-label" class="file-label">Choose Excel File</label>
        <button type="submit" name="import" id="upload-btn">Upload</button>
    </form>
    </div>

    <!-- Table for Today Task -->
  <div class="table-wrapper">


<!-- Today Task Counter -->
  <div id="todayTaskCounter">
      <p class="todays-task">Today's Task</p>
      <a href="#" class="btn today-completed-btn">Completed: <span id="todayCompletedCount">0</span></a>
      <a href="#" class="btn today-pending-btn">Pending: <span id="todayPendingCount">0</span></a>
  </div>



<table id="todaytaskTable" class="display">
 <thead>
     <tr>
         <th>ID</th>
         <th>Task Name</th>
         <th>Task Date</th>
         <th>Task Time</th>
         <th>Priority</th>
         <th>Category</th>
         <th>Status</th>
         <th>Complete</th>
         <th>Attachment</th>
         <th>Delete</th>
         <th>Edit</th>
     </tr>
 </thead>
 <tbody id="todaytaskTableBody">


 </tbody>
 </table>
 </div>

      <!-- Sort By -->

        <form id="sortForm">
      <div class="sort-category">
        <label for="sortCategory">Sort by Category:</label><br>
        <select name="sortCategory" id="sortCategory" onchange="sortTasks()">
          <option value="">All</option>
          <option value="Work">Work</option>
          <option value="Personal">Personal</option>
        </select>
      </div>
    </form>
  
    <!-- //Search Task  -->
    <form id="searchForm" >
    <input type="text" id="searchInput" placeholder="Enter the task to be searched" name="searchInput">
    <button type="submit" id="searchBtn" name="searchBtn">Search</button
    </form>

 
      
    

   <!-- Task Completed Percentage -->
   <div id="taskPercentage">
      <p id="percentageText">Task Completed Percentage: 0%</p>
     </div>

     <!-- Task counter and auto-delete -->
     <div id="taskContainer">
    <!-- Auto-Delete Button (Left Side) -->
    <div id="Auto-Delete">
    <p>Task auto-delete</p>
        <button>Auto-Delete</button>
    </div>

    <!-- Task Counter (Right Side) -->
    <div id="taskCounter">
      <p>Task Counter</p>
        <a href="#" class="btn blue-btn">Completed: <span id="completedCount">0</span></a>
        <a href="#" class="btn red-btn">Pending: <span id="pendingCount">0</span></a>
    </div>
    </div>
      <!--Table for All Task   -->
       
       <div class="table-wrapper">
      

       <table id="taskTable" class="display">
        <thead>
            <tr>
                <th>ID</th>
                <th>Task Name</th>
                <th>Task Date</th>
                <th>Task Time</th>
                <th>Priority</th>
                <th>Category</th>
                <th>Status</th>
                <th>Complete</th>
                <th>Attachment</th>
                <th>Delete</th>
                <th>Edit</th>
            </tr>
        </thead>
        <tbody id="taskTableBody">
   

        </tbody>
        </table>
        </div>

      

  </div>
  

  

  <!-- Edit Modal Page -->
   <div id="edit-modal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>
      <h2>Edit Task </h2>
      <input type="hidden" id="editTaskId" name="task_id">
              <input type="text" id="editTaskName" name="task_name">
              <input type="date" id="editTaskDate" name="task_date"value="<?php echo $task['task_date']; ?>" required>
              <input type="time" id="editTaskTime" name="task_time"value="<?php echo date('H:i', strtotime($task['task_time'])); ?>" required>
              <div class="edit-priority">
                <label>Priority:</label><br>
                <input type="radio" id="editLow" name="priority" value="Low"> Low<br>
                <input type="radio" id="editMedium" name="priority" value="Medium"> Medium<br>
                <input type="radio" id="editHigh" name="priority" value="High"> High
              </div>
              <div class="edit-category">
                <label for="editCategory">Category:</label><br>
                <select name="category" id="editCategory" required>
                  <option value="Work">Work</option>
                  <option value="Personal">Personal</option>
                </select>
              </div>
                 <!-- Task Status Selection -->
                <div class="edit-status">
                  <label for="editStatus">Status:</label><br>
                  <select name="status" id="editStatus" required>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                  </select>
                </div>

              <div>
              <button id="updateTaskBtn">Update Task</button>
              </div>
              
              
      </div>
  </div>

</body>
</html>


