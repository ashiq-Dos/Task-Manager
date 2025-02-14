  // Setting date and time
  document.addEventListener("DOMContentLoaded", function () {
    flatpickr("#inputdate", {
      enableTime: false,
      dateFormat: "d-m-Y",
      minDate: "today"
    });
    flatpickr("#taskTime", {
      enableTime: true,
      noCalendar: true,
      dateFormat: "h:i K"
    });
    flatpickr("#editTaskDate", {
      enableTime: false,
      dateFormat: "d-m-Y",
      minDate: "today"
    });
    flatpickr("#editTaskTime", {
      enableTime: true,
      noCalendar: true,
      dateFormat: "h:i K"
    });

  });



//Fetch Task
let tableInitialized = false;
let dataTableInstance;
function fetchTasks() {

fetch(`Database.php`) // Adjust the URL if needed
  .then(response => response.json())
  .then(data => {
    console.log("Fetched Data",data);
    var table = document.querySelector('#taskTableBody');
     
   
      table.innerHTML = '';  // Clear previous data when it's the first page
     
      let completedCount = 0;
      let pendingCount = 0;
      const today = new Date();

    data.Data.forEach(task => {
      if (task.status === 'completed') {
        completedCount++;
      } else {
        pendingCount++;
      }
      let taskDateTime = new Date(`${task.task_date} ${task.task_time}`);
      const isOverdue = task.status === "pending" && taskDateTime < today
      var row = document.createElement('tr');
      if (isOverdue) {
        row.classList.add("overdue-task");
      }
      row.innerHTML = `
        <td>${task.id}</td>
        <td>${task.task_name}</td>
        <td>${formatDate(task.task_date)}</td>
        <td>${formatTime(task.task_time)}</td>
        <td>${task.priority}</td>
        <td>${task.category}</td>
        <td>${task.status === 'pending' ? 'Pending' : '<span class="completed">Completed</span>'}</td>
        <td>
          ${task.status === 'pending' ? 
            `<form action="Database.php" method="GET">
               <input type="hidden" name="complete_id" value="${task.id}">
               <button type="submit">Complete</button>
             </form>` : '✔️'}
        </td>
            <td>
                  ${task.attachment 
                      ? `<button onclick="viewAttachment(event,'${task.attachment}')">View</button>` 
                      : 'No Attachment'}
              </td>
        <td>
          <button class="delete" onclick="confirmDelete(event, ${task.id})">Delete</button>
        </td>
        <td>
          <button class="edit" onclick="openModal(event, this)" data-id="${task.id}" 
            data-task-name="${task.task_name}" 
            data-task-date="${task.task_date}" 
            data-task-time="${task.task_time}" 
            data-priority="${task.priority}" 
            data-category="${task.category}"
            data-status="${task.status}"
            >

            Edit
          </button>
        </td>
      `;
      table.appendChild(row);
    });
     // Update task counters
     document.getElementById('completedCount').innerText = completedCount;
     document.getElementById('pendingCount').innerText = pendingCount;
    // Reinitialize DataTables after adding new rows
    if (!tableInitialized) {
      dataTableInstance = $('#taskTable').DataTable({
        "paging": true,
        "searching": true,
        "ordering": false,
        "info": true,
        "lengthMenu": [5, 10, 20, 50],
        "pageLength": 5
      });
      tableInitialized = true;
    } else {
      dataTableInstance.destroy();
      dataTableInstance = $('#taskTable').DataTable();
    }
    
  })
  .catch(error => console.error('Error fetching tasks:', error));
}
// Helper function to format date
function formatDate(dateString) {
var date = new Date(dateString);
return `${date.getDate().toString().padStart(2, '0')}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getFullYear()}`;
}

// Helper function to format time
function formatTime(timeString) {
var timeParts = timeString.split(':');  // Split the time string into hours, minutes, and seconds
var date = new Date();

// Set the time using the time parts
date.setHours(parseInt(timeParts[0], 10));
date.setMinutes(parseInt(timeParts[1], 10));
date.setSeconds(parseInt(timeParts[2], 10));

// Use toLocaleTimeString to format the time in 12-hour format (AM/PM)
return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
//Fetch Today Task
let tableInitialised = false;
let dataTableInstances;
let upcomingTasks = [];
function fetchtodayTasks() {
  fetch(`Database.php`) // Adjust the URL if needed
    .then(response => response.json())
    .then(data => {
      console.log("Fetched todays Data", data);
      var table = document.querySelector('#todaytaskTableBody');
      table.innerHTML = ''; // Clear previous data

      let completedCount = 0;
      let pendingCount = 0;

      // Get today's date in YYYY-MM-DD format
      let today = new Date().toISOString().split('T')[0];

      // Filter tasks to only include today's tasks
      let todaysTasks = data.Data.filter(task => task.task_date === today);

      todaysTasks.forEach(task => {
        if (task.status === 'completed') {
          completedCount++;
        } else {
          pendingCount++;
          // Store task time for notification check
          upcomingTasks.push({ id: task.id, name: task.task_name, time: task.task_time });
        

        }

        var row = document.createElement('tr');
        row.innerHTML = `
          <td>${task.id}</td>
          <td>${task.task_name}</td>
          <td>${formatDate(task.task_date)}</td>
          <td>${formatTime(task.task_time)}</td>
          <td>${task.priority}</td>
          <td>${task.category}</td>
          <td>${task.status === 'pending' ? 'Pending' : '<span class="completed">Completed</span>'}</td>
          <td>
            ${task.status === 'pending' ? 
              `<form action="Database.php" method="GET">
                 <input type="hidden" name="complete_id" value="${task.id}">
                 <button type="submit">Complete</button>
               </form>` : '✔️'}
          </td>
          <td>
            ${task.attachment 
                ? `<button onclick="viewAttachment(event,'${task.attachment}')">View</button>` 
                : 'No Attachment'}
          </td>
          <td>
            <button class="delete" onclick="confirmDelete(event, ${task.id})">Delete</button>
          </td>
          <td>
            <button class="edit" onclick="openModal(event, this)" data-id="${task.id}" 
              data-task-name="${task.task_name}" 
              data-task-date="${task.task_date}" 
              data-task-time="${task.task_time}" 
              data-priority="${task.priority}" 
              data-category="${task.category}"
              data-status="${task.status}">
              Edit
            </button>
          </td>
        `;
        table.appendChild(row);
      });

      // Update task counters
      document.getElementById('todayCompletedCount').innerText = completedCount;
      document.getElementById('todayPendingCount').innerText = pendingCount;

      // Reinitialize DataTables
      if (!tableInitialised) {
        dataTableInstances = $('#todaytaskTable').DataTable({
          "paging": true,
          "searching": true,
          "ordering": false,
          "info": true,
          "lengthMenu": [5, 10, 20, 50],
          "pageLength": 5
        });
        tableInitialised = true;
      } else {
        dataTableInstances.destroy();
        dataTableInstances = $('#todaytaskTable').DataTable();
      }
    })
    .catch(error => console.error('Error fetching tasks:', error));
}
function checkTaskNotifications() {
  let now = new Date();
  let currentTime = now.getHours() * 60 + now.getMinutes(); // Convert current time to minutes

  upcomingTasks.forEach(task => {
    let [taskHour, taskMinute] = task.time.split(':').map(Number);
    let taskTimeInMinutes = taskHour * 60 + taskMinute;

    // If the task is exactly 15 minutes away, trigger notification
    if (taskTimeInMinutes - currentTime === 15) {
      playNotificationSound();  
      setTimeout(() => {       
        alert(`Reminder: Your task "${task.name}" is scheduled in 15 minutes!`);
      }, 500); 
    }
  });
}

// Run the notification check every minute
setInterval(checkTaskNotifications, 60000);
function playNotificationSound() {
  let audio = new Audio('assets/sounds/alert.mp3'); // Use your own sound file
  audio.play();
}


// Call fetchTasks when the page loads
window.onload = function() {
fetchTasks();
fetchtodayTasks();
};



function confirmDelete(event, id) {

  event.preventDefault()

  let confirmAction = confirm("Are you sure you want to delete this task?");
  if (confirmAction) {
    window.location.href = "Database.php?delete_id=" + id;
  }
}

//Modal Opening

function openModal(event,button){
  event.preventDefault();
  console.log("the recieved data here:",button)
  const taskId = button.getAttribute('data-id');
  const taskName = button.getAttribute('data-task-name');
  const taskDate = button.getAttribute('data-task-date');
  const taskTime = button.getAttribute('data-task-time');
  const priority = button.getAttribute('data-priority');
  const category = button.getAttribute('data-category');
  const status = button.getAttribute('data-status');


  console.log("Task ID:", taskId);
  console.log("Task Name:", taskName);
  console.log("Task Date:", taskDate);
  console.log("Task Time:", taskTime);
  console.log("Priority:", priority);
  console.log("Category:", category);
  console.log("Status:", status);

  document.querySelector("#editTaskId").value=taskId
  document.querySelector("#editTaskName").value=taskName
  document.querySelector("#editTaskDate").value=taskDate
  document.querySelector("#editTaskTime").value=taskTime

  if(priority==="Low"){
    document.querySelector("#editLow").checked=true
  }
  if(priority==="Medium"){
    document.querySelector("#editMedium").checked=true
    }
    if(priority==="High"){
      document.querySelector("#editHigh").checked=true
      }

  document.querySelector("#editCategory").value=category

  document.querySelector("#editStatus").value=status


  document.querySelector("#edit-modal").style.display = "flex"
}

//Closing Modal
function closeModal(){
  document.querySelector("#edit-modal").style.display = "none"
}



  document.getElementById('file').addEventListener('change', function() {
      let fileName = this.files[0] ? this.files[0].name : "Choose CSV File";
      document.getElementById('file-label').textContent = fileName;
  });


function sortTasks() {
  const sortCategory = document.getElementById('sortCategory').value;
  
  fetch('Database.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ action: 'sortCategory', sortCategory })
  })
  .then(response => response.json())
  .then(data => {
    const taskTableBody = document.getElementById('taskTableBody');
    taskTableBody.innerHTML = ''; // Clear previous table rows
    
    data.Data.forEach(task => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${task.id}</td>
        <td>${task.task_name}</td>
        <td>${formatDate(task.task_date)}</td>
        <td>${formatTime(task.task_time)}</td>
        <td>${task.priority}</td>
        <td>${task.category}</td>
        <td>${task.status}</td>
          <td>${task.status === 'pending' ? 'Pending' : '<span class="completed">Completed</span>'}</td>
        <td>
          ${task.status === 'pending' ? 
            `<form action="Database.php" method="GET">
               <input type="hidden" name="complete_id" value="${task.id}">
               <button type="submit">Complete</button>
             </form>` : '✔️'}
        </td>
           <td>
                  ${task.attachment 
                      ? `<button onclick="viewAttachment(event,'${task.attachment}')">View</button>` 
                      : 'No Attachment'}
              </td>
       
         <td>
          <button class="delete" onclick="confirmDelete(event, ${task.id})">Delete</button>
        </td>
        <td>
          <button class="edit" onclick="openModal(event, this)" data-id="${task.id}" 
            data-task-name="${task.task_name}" 
            data-task-date="${task.task_date}" 
            data-task-time="${task.task_time}" 
            data-priority="${task.priority}" 
            data-category="${task.category}">
            Edit
          </button>
        </td>
      `;
      taskTableBody.appendChild(row);
    });
  })
  .catch(error => console.error('Error fetching tasks:', error));
}

  // Add event listener to form for search

  document.getElementById('searchBtn').addEventListener('click', function(event) {
  event.preventDefault(); // Prevent form from submitting normally
  const searchQuery = document.getElementById('searchInput').value.trim();
  const sortCategory = document.getElementById('sortCategory').value;

      fetch('Database.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({ action: 'search', searchInput: searchQuery,sortCategory: sortCategory })
      })
      .then(response => {
          if (!response.ok) {
              throw new Error('Network response was not ok');
          }
          return response.json();
      })
      .then(data => {
          const taskTableBody = document.getElementById('taskTableBody');
          if (taskTableBody) {
              taskTableBody.innerHTML = ''; // Clear previous rows

              if (data.Data && data.Data.length > 0) {
                  data.Data.forEach(task => {
                      const row = document.createElement('tr');
                      row.innerHTML = `
                          <td>${task.id}</td>
                          <td>${task.task_name}</td>
                          <td>${formatDate(task.task_date)}</td>
                          <td>${formatTime(task.task_time)}</td>
                          <td>${task.priority}</td>
                          <td>${task.category}</td>
                           <td>${task.status === 'pending' ? 'Pending' : '<span class="completed">Completed</span>'}</td>
                            <td>
                              ${task.status === 'pending' ? 
                                `<form action="Database.php" method="GET">
                                  <input type="hidden" name="complete_id" value="${task.id}">
                                  <button type="submit">Complete</button>
                                </form>` : '✔️'}
                            </td>
                             <td>
                                ${task.attachment 
                                    ? `<button onclick="viewAttachment(event,'${task.attachment}')">View</button>` 
                                    : 'No Attachment'}
                            </td>
                          <td>
                                    <button class="delete" onclick="confirmDelete(event, ${task.id})">Delete</button>
                                  </td>
                                  <td>
                                    <button class="edit" onclick="openModal(event, this)" data-id="${task.id}" 
                                      data-task-name="${task.task_name}" 
                                      data-task-date="${task.task_date}" 
                                      data-task-time="${task.task_time}" 
                                      data-priority="${task.priority}" 
                                      data-category="${task.category}">
                                      Edit
                                    </button>
                                  </td>
                      `;
                      taskTableBody.appendChild(row);
                  });
              } else {
                  taskTableBody.innerHTML = '<tr><td colspan="10">No tasks found.</td></tr>';
              }
          } else {
              console.error('taskTableBody element not found.');
          }
      })
      .catch(error => {
          console.error('Error fetching search results:', error);
      });
});

// Upload Task Document Function

document.addEventListener("DOMContentLoaded", function () {
    const uploadBtn = document.getElementById("uploadBtn");
    const attachmentInput = document.getElementById("attachment");

    // When the button is clicked, trigger file input
    uploadBtn.addEventListener("click", function () {
        attachmentInput.click();
    });

    // Update button text when file is selected
    attachmentInput.addEventListener("change", function () {
        if (attachmentInput.files.length > 0) {
            uploadBtn.textContent = attachmentInput.files[0].name; // Display file name
        } else {
            uploadBtn.textContent = "Upload File"; // Reset if no file selected
        }
    });
});

function viewAttachment(event,filePath) {
event.preventDefault();
  // Ensure only the file name is encoded, NOT the slashes
  const directory = filePath.substring(0, filePath.lastIndexOf("/") + 1); // Extract the folder path
  const fileName = filePath.substring(filePath.lastIndexOf("/") + 1); // Extract the file name
  const encodedFileName = encodeURIComponent(fileName); // Encode only the file name

  const fullPath = `/todo/${directory}${encodedFileName}`; // Correctly constructed URL

  console.log("Full Path:", fullPath); // Debugging

  // Extract file extension
  const fileExtension = filePath.split('.').pop().toLowerCase();

  // Open images in a new window
  if (['jpg', 'jpeg', 'png', 'gif','webp'].includes(fileExtension)) {
      const imageWindow = window.open();
      imageWindow.document.write(`<img src="${fullPath}" alt="Attachment" style="width: 50%; height: auto;">`);
  } 
  // Open PDFs in a new window
  else if (fileExtension === 'pdf') {
      window.open(fullPath, '_blank');
  } 
  // Alert for unsupported files
  else {
      alert("Unsupported file type");
  }
}

//Total Task Percentage

function fetchTaskPercentage() {
    fetch("Database.php?action=percentage") 
      .then(response => {
        return response.json(); 
    })
      .then(data => {
        console.log("percentage data",data);
        let percentageText = document.getElementById("percentageText");
        percentageText.textContent = `Task Completed Percentage: ${data.percentage}%`;
      })
      .catch(error => console.error("Error fetching task percentage:", error));
  }
  
  document.addEventListener("DOMContentLoaded", fetchTaskPercentage);
  // Update Task

  document.getElementById("updateTaskBtn").addEventListener("click", function () {
    const taskId = document.getElementById("editTaskId").value;
    const taskName = document.getElementById("editTaskName").value;
    const taskDate = document.getElementById("editTaskDate").value;
    const taskTime = document.getElementById("editTaskTime").value;
    const priority = document.querySelector("input[name='priority']:checked")?.value || "";
    const category = document.getElementById("editCategory").value;
    const status = document.getElementById("editStatus").value;

    // Check if required fields are filled
    if (!taskName || !taskDate || !taskTime || !priority || !category || !status) {
        alert("Please fill all required fields.");
        return;
    }

    const formData = {
        action: "updateTask", // Specify the action
        task_id: taskId,
        task_name: taskName,
        task_date: taskDate,
        task_time: taskTime,
        priority: priority,
        category: category,
        status: status
    };

    // Send a POST request with action included
    fetch('Database.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json()) // Convert response to JSON
    .then(result => {
        if (result.message) {
            alert(result.message); // Show success message
            closeModal(); // Close the modal
        } else {
            alert(result.error || "Error updating task."); // Show error message
        }
    })
    .catch(error => {
        console.error("Fetch error:", error);
        alert("Something went wrong! Please try again.");
    });
});

//Auto Delete Task
document.getElementById("Auto-Delete").addEventListener("click",function(event){
  event.preventDefault();
  fetch("Database.php", {
    method: "POST",
    headers: {
        "Content-Type": "application/json"
    },
    body: JSON.stringify({ action: "auto_delete" })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert("Completed tasks deleted successfully!");
        fetchTasks();
    } else {
        alert("Error deleting tasks: " + data.message);
    }
})
.catch(error => console.error("Error:", error));
})
