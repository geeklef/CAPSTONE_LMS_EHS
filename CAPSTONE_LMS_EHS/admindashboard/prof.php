<?php include '../config/db_connect.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin - Teachers</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />


  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <style>
    
    @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap');

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Quicksand", sans-serif;
    }

    body {
      display: flex;
      height: 100vh;
      background-color: #f4f7f9;
      overflow-x: hidden;
    }

    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: 250px;
      background-color: #004aad;
      color: white;
      padding: 20px;
      overflow-y: auto;
    }

     /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      width: 250px;
      background-color: #004aad;
      color: white;
      padding: 20px;
      overflow-y: auto;
    }

    .sidebar h3 {
      margin-left: 5px;
      margin-bottom: 40px;
    }

    .sidebar .menu a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 0;
      color: rgb(202, 201, 201);
      text-decoration: none;
      margin-bottom: 10px;
      font-size: 16px;
      transition: all 0.3s ease;
    }

    .sidebar .menu a:hover {
      background-color: rgba(255, 255, 255, 0.15);
      padding-left: 10px;
      border-radius: 5px;
    }

    .sidebar .menu a.active {
      color: white;
      font-weight: 600;
      position: relative;
    }

    .sidebar .menu a.active::before {
      content: "";
      position: absolute;
      left: -20px;
      top: 0;
      bottom: 0;
      width: 5px;
      background-color: white;
      border-radius: 10px;
    }

    .sidebar .menu a .material-icons {
      font-size: 22px;
    }

    .main {
      margin-left: 250px;
      flex: 1;
      padding: 20px;
    }

    .topbar {
      background-color: #004aad;
      padding: 20px;
      color: white;
      font-size: 24px;
      border-radius: 10px;
    }

    .section {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      margin-top: 30px;
    }

    .section h3 {
      margin-bottom: 20px;
    }

    .btn {
      background-color: white;
      color: #004aad;
      padding: 8px 15px;
      border: 2px solid #004aad;
      border-radius: 40px;
      cursor: pointer;
      font-size: 14px;
      margin-top: 30px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .btn:hover {
      background-color: #003b8c;
      color: white;
    }

    /* Modal (DM style) */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }

    .modal-visible {
      display: flex !important;
    }

    .modal-content {
      background: white;
      padding: 30px;
      border-radius: 15px;
      max-width: 500px;
      width: 90%;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      position: relative;
      top: 0;
      left: 0;
      transform: none;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: stretch;
    }

    .modal-close {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 24px;
      cursor: pointer;
    }

    table th {
      background-color: #004aad;
      color: white;
      text-align: left;
      padding: 10px;
    }

    table td {
      padding: 10px;
    }

    table tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    /* Choice Modal Buttons */
    .choice-btn {
      background-color: #004aad;
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-size: 15px;
      width: 100%;
      margin: 10px 0;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: 0.3s ease;
    }

    .choice-btn:hover {
      background-color: #003b8c;
    }

    input[type="file"] {
      border: 1px solid #ccc;
      padding: 8px;
      border-radius: 5px;
      width: 100%;
    }

    label {
      display: block;
      margin-top: 10px;
    }

    input[type="text"],
    select {
      margin-top: 5px;
    }

    </style>

</head>

<body>
  <div class="sidebar">
    <div class="profile" style="text-align: center;">
      <span class="material-icons" style="font-size: 100px;">account_circle</span>
      <h3>Administrator</h3>
    </div>
 <div class="menu">
      <a href="adminhome.php"><span class="material-icons">dashboard</span> Dashboard</a>
      <a href="courses.php" ><span class="material-icons">schedule</span> Schedules</a>
      <a href="prof.php" class="active"><span class="material-icons">co_present</span> Teacher</a>
      <a href="student.php"><span class="material-icons">backpack</span> Student</a>
       <a href="settings.php"><span class="material-icons">settings</span> Settings</a>
        <a href="/CAPSTONE_LMS_EHS/auth/login.php">
        <span class="material-icons">logout</span>
        Logout
      </a>
    </div>
  </div>

  <div class="main">
    <div class="topbar">Faculty Members</div>

    <div class="add-activity-btn">
      <button class="btn" onclick="openChoiceModal()">
        <span class="material-icons">add</span> Add New Teacher
      </button>
    </div>

    <div class="section">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h3 style="margin:0;">Instructor Lists</h3>
        <div style="display:flex; gap:10px; align-items:center;">
          <input type="text" id="searchInput" placeholder="Search teacher..." style="padding:8px; border:1px solid #ccc; border-radius:5px; width:200px;">
          <select id="departmentFilter" style="padding:8px 12px; height:36px; border:1px solid #ccc; border-radius:5px;">
            <option value="">All Departments</option>
            <option value="Science">Science</option>
            <option value="Mathematics">Mathematics</option>
            <option value="English">English</option>
          </select>

          <button id="exportExcel" style="background:#004aad; color:white; border-radius:5px; padding:8px 12px; display:flex; align-items:center; border:none;">
            <span class="material-icons" style="margin-right:5px;">table_view</span> XLSX
          </button>
          <button id="exportPDF" style="background:#004aad; color:white; border-radius:5px; padding:8px 12px; display:flex; align-items:center; border:none;">
            <span class="material-icons" style="margin-right:5px;">picture_as_pdf</span> PDF
          </button>
        </div>
      </div>

      <table id="teacherTable" style="width:100%; border-collapse: collapse;">
        <thead>
          <tr>
            <th>Teacher ID</th>
            <th>Username</th>
            <th>Name</th>
            <th>Department</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="teacherBody">
          <!-- Dynamic data -->
        </tbody>
      </table>
    </div>
  </div>

  <!-- CHOICE MODAL -->
  <div id="choiceModal" class="modal-overlay">
    <div class="modal-content">
      <span class="modal-close" onclick="closeChoiceModal()">&times;</span>
      <h2 style="color:#004aad; text-align:center;">Add New Teacher</h2>
      <p style="text-align:center; margin:15px 0;">Choose a registration method:</p>
      <button class="choice-btn" onclick="openAddModal()"><span class="material-icons">person_add</span> One by One Registration</button>
      <button class="choice-btn" onclick="openUploadModal()"><span class="material-icons">upload_file</span> Upload Excel File</button>
    </div>
  </div>

  <!-- ONE BY ONE MODAL -->
  <div id="addModal" class="modal-overlay">
    <div class="modal-content">
      <span class="modal-close" onclick="closeAddModal()">&times;</span>
      <h2 style="margin-bottom:20px; color:#004aad;">One by One Registration</h2>
      <form id="addForm">
        <label>Username</label><input type="text" name="teacher_user" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>Password</label><input type="text" name="teacher_pass" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>First Name</label><input type="text" name="first_name" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>Last Name</label><input type="text" name="last_name" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>Email</label><input type="email" name="email" style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>Subject</label>
        <select name="subject_id" style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
          <option value="101">Science</option>
          <option value="102">Mathematics</option>
          <option value="103">English</option>
        </select>
        <label>Department</label>
        <select name="department" style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
          <option value="Science">Science</option>
          <option value="Mathematics">Mathematics</option>
          <option value="English">English</option>
        </select>
        <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
          <button type="button" onclick="closeAddModal()" style="background:#ccc; border:none; padding:10px 20px; border-radius:5px;">Cancel</button>
          <button type="submit" style="background:#004aad; color:white; border:none; padding:10px 20px; border-radius:5px;">Add</button>
        </div>
      </form>
    </div>
  </div>

  <!-- UPLOAD MODAL -->
  <div id="uploadModal" class="modal-overlay">
    <div class="modal-content">
      <span class="modal-close" onclick="closeUploadModal()">&times;</span>
      <h2 style="color:#004aad; text-align:center;">Upload Excel File</h2>
      <p style="text-align:center; margin-bottom:15px;">Select an Excel file containing teacher details (.xlsx or .xls)</p>
      <input type="file" id="uploadFile" accept=".xlsx,.xls" />
      <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" onclick="closeUploadModal()" style="background:#ccc; border:none; padding:10px 20px; border-radius:5px;">Cancel</button>
        <button type="button" id="uploadButton" style="background:#004aad; color:white; border:none; padding:10px 20px; border-radius:5px;">Upload</button>
      </div>
    </div>
  </div>

  <!-- EDIT MODAL -->
  <div id="editModal" class="modal-overlay">
    <div class="modal-content">
      <span class="modal-close" onclick="closeEditModal()">&times;</span>
      <h2 style="margin-bottom: 20px; color: #004aad; text-align:center;">Edit Teacher</h2>
      <form id="editForm">
        <input type="hidden" id="edit_teacher_id" name="teacher_id">
        <label>Username</label><input type="text" id="edit_teacher_user" name="teacher_user" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>Password</label><input type="text" id="edit_password" name="teacher_pass" style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>First Name</label><input type="text" id="edit_first_name" name="first_name" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>Last Name</label><input type="text" id="edit_last_name" name="last_name" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>Email</label><input type="email" id="edit_email" name="email" style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>Subject</label>
        <select id="edit_subject" name="subject_id" style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
          <option value="101">Science</option>
          <option value="102">Mathematics</option>
          <option value="103">English</option>
        </select>
        <label>Department</label>
        <select id="edit_department" name="department" style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
          <option value="Science">Science</option>
          <option value="Mathematics">Mathematics</option>
          <option value="English">English</option>
        </select>
        <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
          <button type="button" onclick="closeEditModal()" style="background:#ccc; border:none; padding:10px 20px; border-radius:5px;">Cancel</button>
          <button type="button" onclick="saveEdit()" style="background:#004aad; color:white; border:none; padding:10px 20px; border-radius:5px;">Save</button>
        </div>
      </form>
    </div>
  </div>


<script>
  // =============================
  // FETCH TEACHERS
  // =============================
function fetchTeachers() {
  const search = document.getElementById('searchInput').value.trim();
  const dept = document.getElementById('departmentFilter').value.trim();

  fetch(`../api/admin/admin_prof/get_prof.php?search=${search}&department=${dept}`)
    .then(res => res.text())
    .then(data => {
      // Initialize table if not already done
      if (!$.fn.DataTable.isDataTable('#teacherTable')) {
        $('#teacherTable').DataTable({
          paging: true,
          ordering: true,
          searching: false,
          dom: 'Bfrtip',
          buttons: []
        });
      }

      // Get DataTable instance
      let table = $('#teacherTable').DataTable();

      // Clear existing data
      table.clear();

      // Convert fetched HTML rows to jQuery objects and add them
      let rows = $(data);
      rows.each(function() {
        let tds = $(this).find('td').map(function() {
          return $(this).html();
        }).get();
        table.row.add(tds);
      });

      // Redraw table
      table.draw();
    })
    .catch(err => console.error(err));
}



  document.getElementById('searchInput').addEventListener('keyup', fetchTeachers);
  document.getElementById('departmentFilter').addEventListener('change', fetchTeachers);
  window.addEventListener('DOMContentLoaded', fetchTeachers);


  // =============================
  // MODAL FUNCTIONS
  // =============================
  function openChoiceModal() { document.getElementById('choiceModal').classList.add('modal-visible'); }
  function closeChoiceModal() { document.getElementById('choiceModal').classList.remove('modal-visible'); }
  function openAddModal() { closeChoiceModal(); document.getElementById('addModal').classList.add('modal-visible'); }
  function closeAddModal() { document.getElementById('addModal').classList.remove('modal-visible'); }
  function openUploadModal() { closeChoiceModal(); document.getElementById('uploadModal').classList.add('modal-visible'); }
  function closeUploadModal() { document.getElementById('uploadModal').classList.remove('modal-visible'); }
  function closeEditModal() { document.getElementById('editModal').classList.remove('modal-visible'); }

  // =============================
  // ADD TEACHER
  // =============================
  document.getElementById('addForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);

    fetch('../api/admin/admin_prof/add_prof.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        if(data.status === 'success') {
          closeAddModal();
          fetchTeachers();
        }
      })
      .catch(err => { console.error(err); alert('Error adding teacher.'); });
  });

  // =============================
  // UPLOAD EXCEL
  // =============================
  document.getElementById('uploadButton').addEventListener('click', function(){
    const file = document.getElementById('uploadFile').files[0];
    if(!file){ alert("Select an Excel file."); return; }

    const formData = new FormData();
    formData.append('file', file);

    fetch('../api/admin/admin_prof/upload_prof.php', { method:'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        alert(data.message);
        if(data.status === 'success') {
          closeUploadModal();
          fetchTeachers();
        }
      })
      .catch(err => { console.error(err); alert("Error uploading Excel file."); });
  });

  // =============================
  // EDIT TEACHER
  // =============================
function openEditModal(teacherId) {
  fetch(`../api/admin/admin_prof/get_prof_details.php?teacher_id=${teacherId}`)
    .then(res => res.json())
    .then(data => {

      // Check server response
      if (!data || data.status !== "success") {
        alert("Failed to fetch teacher details.");
        return;
      }

      const t = data.teacher; // <-- FIXED

      // Fill modal fields
      document.getElementById('edit_teacher_id').value = t.teacher_id;
      document.getElementById('edit_teacher_user').value = t.teacher_user;
      document.getElementById('edit_password').value = t.teacher_pass;
      document.getElementById('edit_first_name').value = t.first_name;
      document.getElementById('edit_last_name').value = t.last_name;
      document.getElementById('edit_email').value = t.email;
      document.getElementById('edit_subject').value = t.subject_id;
      document.getElementById('edit_department').value = t.department;

      document.getElementById('editModal').classList.add('modal-visible');
    })
    .catch(err => {
      console.error(err);
      alert('Failed to fetch teacher details.');
    });
}


  function saveEdit() {
    const formData = new FormData(document.getElementById('editForm'));

    fetch('../api/admin/admin_prof/edit_prof.php', { method:'POST', body: formData })
      .then(res => res.text())
      .then(data => {
        alert(data);
        closeEditModal();
        fetchTeachers();
      })
      .catch(err => { console.error(err); alert('Error saving edits.'); });
  }

  // =============================
  // DELETE TEACHER
  // =============================
  function deleteProfessor(teacherId){
    if(confirm("Are you sure you want to delete this teacher?")){
      fetch('../api/admin/admin_prof/delete_prof.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'teacher_id=' + encodeURIComponent(teacherId)
      })
      .then(res => res.text())
      .then(data => { alert(data); fetchTeachers(); })
      .catch(err => console.error(err));
    }
  }

  // =============================
  // EXPORT BUTTONS
  // =============================
  $(document).on('click', '#exportExcel', function () {
    $('#teacherTable').DataTable().button().add(0, { extend: 'excelHtml5', title: 'Teacher_List_Export' });
    $('#teacherTable').DataTable().button(0).trigger();
    $('#teacherTable').DataTable().buttons().remove();
  });

  $(document).on('click', '#exportPDF', function () {
    $('#teacherTable').DataTable().button().add(0, { extend: 'pdfHtml5', title: 'Teacher_List_Export', orientation: 'landscape', pageSize: 'A4' });
    $('#teacherTable').DataTable().button(0).trigger();
    $('#teacherTable').DataTable().buttons().remove();
  });
</script>


</body>
</html>
