<?php include '../config/db_connect.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin - Students</title>
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
      <a href="adminhome.php" ><span class="material-icons">dashboard</span> Dashboard</a>
      <a href="courses.php"><span class="material-icons">schedule</span> Schedules</a>
      <a href="prof.php"><span class="material-icons">co_present</span> Teacher</a>
      <a href="student.php"class="active"><span class="material-icons">backpack</span> Student</a>
       <a href="settings.php"><span class="material-icons">settings</span> Settings</a>
        <a href="/CAPSTONE_LMS_EHS/auth/login.php">
        <span class="material-icons">logout</span>
        Logout
      </a>
    </div>
  </div>

  <div class="main">
    <div class="topbar">Enrolled Students</div>

    <div class="add-activity-btn">
      <button class="btn" onclick="openChoiceModal()">
        <span class="material-icons">add</span> Add New Student
      </button>
    </div>

    <div class="section">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h3 style="margin:0;">Student Lists</h3>
        <div style="display:flex; gap:10px; align-items:center;">
          <input type="text" id="searchInput" placeholder="Search student..." style="padding:8px; border:1px solid #ccc; border-radius:5px; width:200px;">
          <select id="strandFilter" style="padding:8px 12px; height:36px; border:1px solid #ccc;">
            <option value="">All Strand</option>
            <option value="GAS">GAS</option>
            <option value="HUMSS">HUMSS</option>
            <option value="STEM">STEM</option>
            <option value="ABM">ABM</option>
          </select>
          <select id="gradeFilter" style="padding:8px 12px; height:36px; border:1px solid #ccc;">
            <option value="">All Grades</option>
            <option value="11">Grade 11</option>
            <option value="12">Grade 12</option>
          </select>

          <button id="exportExcel" style="background:#004aad; color:white; border-radius:5px; padding:8px 12px; display:flex; align-items:center; border:none;">
            <span class="material-icons" style="margin-right:5px;">table_view</span> XLSX
          </button>
          <button id="exportPDF" style="background:#004aad; color:white; border-radius:5px; padding:8px 12px; display:flex; align-items:center; border:none;">
            <span class="material-icons" style="margin-right:5px;">picture_as_pdf</span> PDF
          </button>
        </div>
      </div>

      <table id="studentTable" style="width:100%; border-collapse: collapse;">
        <thead>
          <tr>
            <th>School ID</th>
            <th>Username</th>
            <th>Name</th>
            <th>Grade</th>
            <th>Strand</th>
            <th>Section</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="studentBody">
          <!-- Dynamic data -->
        </tbody>
      </table>
    </div>
  </div>

  <!-- CHOICE MODAL -->
  <div id="choiceModal" class="modal-overlay">
    <div class="modal-content">
      <span class="modal-close" onclick="closeChoiceModal()">&times;</span>
      <h2 style="color:#004aad; text-align:center;">Add New Student</h2>
      <p style="text-align:center; margin:15px 0;">Choose a registration method:</p>
      <button class="choice-btn" onclick="openAddModal()"><span class="material-icons">person_add</span> One by One Registration</button>
      <button class="choice-btn" onclick="openUploadModal()"><span class="material-icons">upload_file</span> Upload Excel File</button>
    </div>
  </div>

  <!-- ADD STUDENT MODAL -->
  <div id="addModal" class="modal-overlay">
    <div class="modal-content">
      <span class="modal-close" onclick="closeAddModal()">&times;</span>
      <form id="addForm">
        <label>Student_ID</label><input type="text" name="stud_id" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>Username</label><input type="text" name="stud_user" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>Password</label><input type="text" name="password" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>First Name</label><input type="text" name="first_name" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>Last Name</label><input type="text" name="last_name" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>Email</label><input type="email" name="email" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <label>Grade Level</label>
        <select name="grade_level" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
          <option value="11">11</option>
          <option value="12">12</option>
        </select>
        <label>Strand</label>
        <select name="strand" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
          <option value="STEM">STEM</option>
          <option value="ABM">ABM</option>
          <option value="HUMSS">HUMSS</option>
          <option value="GAS">GAS</option>
        </select>
        <label>Section</label><input type="text" name="section" required style="width:100%; padding:8px; border-radius:5px; border:1px solid #ccc;">
        <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
          <button type="button" onclick="closeAddModal()" style="background:#ccc; border:none; padding:10px 20px; border-radius:5px;">Cancel</button>
          <button type="submit" style="background:#004aad; color:white; border:none; padding:10px 20px; border-radius:5px;">Add</button>
        </div>
      </form>
    </div>
  </div>

<!-- VIEW ENROLLMENTS MODAL -->
<div id="enrollModal" class="modal-overlay">
  <div class="modal-content" style="
        width: 90%;       /* take 90% of viewport width */
        max-width: 1400px; /* cap the maximum width */
        height: 40vh;     /* 80% of viewport height */
        overflow-y: auto; /* allow vertical scroll if table too long */
        padding: 30px;
    ">
    <span class="modal-close" onclick="closeEnrollModal()">&times;</span>
    <h2 style="color:#004aad; text-align:center;">Enrolled Subjects</h2>
    <table id="enrollTable" style="width:100%; border-collapse: collapse; margin-top:15px;">
      <thead>
        <tr>
          <th>Course Name</th>
          <th>Day</th>
          <th>Start Time</th>
          <th>End Time</th>
          <th>Teacher</th>
          <th>Section</th>
          <th>Strand</th>
        </tr>
      </thead>
      <tbody id="enrollBody">
        <!-- Dynamic data -->
      </tbody>
    </table>
  </div>
</div>

  <!-- UPLOAD STUDENT MODAL -->
  <div id="uploadModal" class="modal-overlay">
    <div class="modal-content">
      <span class="modal-close" onclick="closeUploadModal()">&times;</span>
      <h2 style="color:#004aad; text-align:center;">Upload Excel File</h2>
      <input type="file" id="uploadFile" accept=".xlsx,.xls" />
      <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
        <button type="button" onclick="closeUploadModal()">Cancel</button>
        <button type="button" id="uploadButton">Upload</button>
      </div>
    </div>
  </div>

  <!-- EDIT STUDENT MODAL -->
  <div id="editModal" class="modal-overlay">
    <div class="modal-content">
      <span class="modal-close" onclick="closeEditModal()">&times;</span>
      <h2 style="margin-bottom: 20px; color: #004aad; text-align:center;">Edit Student</h2>
      <form id="editForm">
        <input type="hidden" id="edit_stud_id" name="stud_id">
        <label>Username</label><input type="text" id="edit_stud_user" name="stud_user" required>
        <label>Password</label><input type="text" id="edit_password" name="password">
        <label>First Name</label><input type="text" id="edit_first_name" name="first_name" required>
        <label>Last Name</label><input type="text" id="edit_last_name" name="last_name" required>
        <label>Email</label><input type="email" id="edit_email" name="email">
        <label>Grade Level</label>
        <select id="edit_grade" name="grade_level" required>
          <option value="11">11</option>
          <option value="12">12</option>
        </select>
        <label>Strand</label>
        <select id="edit_strand" name="strand" required>
          <option value="STEM">STEM</option>
          <option value="ABM">ABM</option>
          <option value="HUMSS">HUMSS</option>
          <option value="GAS">GAS</option>
        </select>
        <label>Section</label><input type="text" id="edit_section" name="section" required>
        <label>Status</label>
        <select id="edit_status" name="status" required>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>
        <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
          <button type="button" onclick="closeEditModal()">Cancel</button>
          <button type="button" onclick="saveEdit()">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script>


    document.getElementById('searchInput').addEventListener('keyup', fetchStudents);
    document.getElementById('strandFilter').addEventListener('change', fetchStudents);
    document.getElementById('gradeFilter').addEventListener('change', fetchStudents);
    window.onload = fetchStudents;

    // MODAL FUNCTIONS
    function openChoiceModal() { document.getElementById('choiceModal').classList.add('modal-visible'); }
    function closeChoiceModal() { document.getElementById('choiceModal').classList.remove('modal-visible'); }
    function openAddModal() { closeChoiceModal(); document.getElementById('addModal').classList.add('modal-visible'); }
    function closeAddModal() { document.getElementById('addModal').classList.remove('modal-visible'); }
    function openUploadModal() { closeChoiceModal(); document.getElementById('uploadModal').classList.add('modal-visible'); }
    function closeUploadModal() { document.getElementById('uploadModal').classList.remove('modal-visible'); }
    function closeEditModal() { document.getElementById('editModal').classList.remove('modal-visible'); }

    // ADD STUDENT ONE-BY-ONE
    document.getElementById('addForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      fetch('../api/admin/admin_student/add_students.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          alert(data.message);
          if(data.status==='success'){ closeAddModal(); fetchStudents(); }
        }).catch(err => { console.error(err); alert('Error adding student.'); });
    });

    // UPLOAD EXCEL
    document.getElementById('uploadButton').addEventListener('click', function(){
      const file = document.getElementById('uploadFile').files[0];
      if(!file){ alert("Select an Excel file."); return; }
      const formData = new FormData(); formData.append('file', file);
      fetch('../api/admin/admin_student/upload_students.php',{ method:'POST', body:formData })
        .then(res=>res.json())
        .then(data=>{ alert(data.message); if(data.status==='success'){ closeUploadModal(); fetchStudents(); } })
        .catch(err=>{ console.error(err); alert("Error uploading Excel file."); });
    });

    // EDIT STUDENT
function openEditModal(studId){
  fetch(`../api/admin/admin_student/get_student_details.php?stud_id=${studId}`)
    .then(res => res.json())
    .then(data => {
      if(data.error){ alert(data.error); return; }
      document.getElementById('edit_stud_id').value = data.stud_id;
      document.getElementById('edit_stud_user').value = data.stud_user;
      document.getElementById('edit_password').value = data.password;
      document.getElementById('edit_first_name').value = data.first_name;
      document.getElementById('edit_last_name').value = data.last_name;
      document.getElementById('edit_email').value = data.email;
      document.getElementById('edit_grade').value = data.grade_level;
      document.getElementById('edit_strand').value = data.strand;
      document.getElementById('edit_section').value = data.section;
      document.getElementById('edit_status').value = data.status;
      document.getElementById('editModal').classList.add('modal-visible');
    }).catch(err => { console.error(err); alert('Failed to fetch student details.'); });
}


    function saveEdit(){
      const formData = new FormData(document.getElementById('editForm'));
      fetch('../api/admin/admin_student/edit_students.php',{ method:'POST', body:formData })
        .then(res=>res.text())
        .then(data=>{ alert(data); closeEditModal(); fetchStudents(); })
        .catch(err=>{ console.error(err); alert('Error saving edits.'); });
    }

    // DELETE STUDENT
    function deleteStudent(studId){
      if(confirm("Are you sure you want to delete this student?")){
        fetch('../api/admin/admin_student/delete_students.php',{
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:'stud_id='+encodeURIComponent(studId)
        }).then(res=>res.text()).then(data=>{ alert(data); fetchStudents(); }).catch(err=>console.error(err));
      }
    }

    // DataTable Export
let studentTable;

function fetchStudents() {
  const search = encodeURIComponent($('#searchInput').val());
  const strand = encodeURIComponent($('#strandFilter').val());
  const grade  = encodeURIComponent($('#gradeFilter').val());

  fetch(`../api/admin/admin_student/get_students.php?search=${search}&strand=${strand}&grade=${grade}`)
    .then(res => res.text())
    .then(data => {
      // Destroy previous DataTable safely
      if ($.fn.DataTable.isDataTable('#studentTable')) {
        studentTable.clear().destroy();
      }

      // Update table body
      $('#studentBody').html(data);

      // Initialize DataTable after DOM update
      setTimeout(() => {
        studentTable = $('#studentTable').DataTable({
          paging: true,
          ordering: true,
          searching: false, // external search
          lengthChange: false,
          columnDefs: [
            { orderable: false, targets: -1 } // Disable sorting on Action column
          ],
          dom: 'Bfrtip',
          buttons: [
            {
              extend: 'excelHtml5',
              title: 'Student_List_Export',
              exportOptions: { columns: ':not(:last-child)' }
            },
            {
              extend: 'pdfHtml5',
              title: 'Student_List_Export',
              orientation: 'landscape',
              pageSize: 'A4',
              exportOptions: { columns: ':not(:last-child)' }
            }
          ],
          initComplete: function() {
            $('.dt-buttons').hide(); // hide default buttons
          }
        });

        // Bind custom export buttons
        $('#exportExcel').off('click').on('click', () => studentTable.button(0).trigger());
        $('#exportPDF').off('click').on('click', () => studentTable.button(1).trigger());
      }, 10);
    })
    .catch(err => console.error('Error fetching students:', err));
}
function openEnrollModal() { document.getElementById('enrollModal').classList.add('modal-visible'); }
function closeEnrollModal() { document.getElementById('enrollModal').classList.remove('modal-visible'); }

// VIEW ENROLLMENTS
function viewEnrollments(studId){
  fetch(`../api/admin/admin_student/get_enrollments.php?stud_id=${studId}`)
    .then(res => res.json())
    .then(data => {
      if(data.error){ alert(data.error); return; }

      let html = '';
      data.forEach(row => {
        html += `<tr>
                  <td>${row.course_name}</td>
                  <td>${row.day}</td>
                  <td>${row.time_start}</td>
                  <td>${row.time_end}</td>
                  <td>${row.prof_name}</td>
                  <td>${row.section}</td>
                  <td>${row.strand}</td>
                </tr>`;
      });

      if(data.length === 0){
        html = '<tr><td colspan="7" style="text-align:center;">No enrolled subjects found.</td></tr>';
      }

      document.getElementById('enrollBody').innerHTML = html;
      openEnrollModal();
    })
    .catch(err => { console.error(err); alert('Error fetching enrollments.'); });
}

// DOWNLOAD ENROLLMENTS
function downloadEnrollments(studId){
  fetch(`../api/admin/admin_student/download_enrollments.php?stud_id=${studId}`)
    .then(res => res.blob())
    .then(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `Student_${studId}_Enrollments.xlsx`; // filename
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
    })
    .catch(err => { console.error(err); alert('Error downloading file.'); });
}

  </script>

</body>
</html>
