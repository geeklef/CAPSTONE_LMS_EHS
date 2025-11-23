<?php include '../config/db_connect.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Course Management</title>
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
    * { margin:0; padding:0; box-sizing:border-box; font-family:"Quicksand", sans-serif; }
    body { display:flex; height:100vh; background-color:#f4f7f9; }
    .sidebar { position:fixed; top:0; left:0; height:100vh; width:250px; background-color:#004aad; color:white; padding:20px; overflow-y:auto; }
    .sidebar h3 { margin-left:5px; margin-bottom:40px; }
    .sidebar .menu a { display:flex; align-items:center; gap:10px; padding:10px 0; color:rgb(202,201,201); text-decoration:none; margin-bottom:10px; font-size:16px; transition: all 0.3s ease; }
    .sidebar .menu a:hover { background-color: rgba(255,255,255,0.15); padding-left:10px; border-radius:5px; }
    .sidebar .menu a.active { color:white; font-weight:600; position:relative; }
    .sidebar .menu a.active::before { content:""; position:absolute; left:-20px; top:0; bottom:0; width:5px; background-color:white; border-radius:10px; }
    .sidebar .menu a .material-icons { font-size:22px; }
    .main { margin-left:250px; flex:1; padding:20px; }
    .topbar { background-color:#004aad; padding:20px; color:white; font-size:24px; border-radius:10px; }
    .section { background:white; padding:20px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); margin-top:30px; }
    .section h3 { margin-bottom:20px; }
    .btn { background-color:white; color:#004aad; padding:8px 15px; border:2px solid #004aad; border-radius:40px; cursor:pointer; font-size:14px; margin-top:30px; display:inline-flex; align-items:center; gap:5px; text-decoration:none; }
    .btn:hover { background-color:#003b8c; color:white; }
    /* Modal */
    .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:9999; }
    .modal-visible { display:flex !important; }
    .modal-content { background:white; padding:30px; border-radius:15px; max-width:500px; width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.2); position:relative; text-align:center; animation: fadeIn 0.2s ease-in-out; }
    .modal-close { position:absolute; top:10px; right:15px; font-size:24px; cursor:pointer; }
    .modal-content input, .modal-content select { width:100%; padding:8px; margin:5px 0; border:1px solid #ccc; border-radius:5px; font-family:'Quicksand', sans-serif; }
    .modal-content button[type="submit"], .modal-content button[type="button"] { margin-top:15px; background-color:#004aad; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; }
    .modal-content button[type="submit"]:hover { background-color:#003b8c; }
    .modal-content button[type="button"] { background-color:#aaa; }
    .modal-content button[type="button"]:hover { background-color:#888; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
  </style>
</head>

<body>
  <div class="sidebar">
    <div class="profile" style="text-align:center;">
      <span class="material-icons" style="font-size:100px;">account_circle</span>
      <h3>Administrator</h3>
    </div>
    <div class="menu">
      <a href="adminhome.php"><span class="material-icons">dashboard</span> Dashboard</a>
      <a href="courses.php" class="active"><span class="material-icons">schedule</span> Schedules</a>
      <a href="prof.php"><span class="material-icons">co_present</span> Teacher</a>
      <a href="student.php"><span class="material-icons">backpack</span> Student</a>
      <a href="settings.php"><span class="material-icons">settings</span> Settings</a>
      <a href="/CAPSTONE_LMS_EHS/auth/login.php"><span class="material-icons">logout</span> Logout</a>
    </div>
  </div>

  <div class="main">
    <div class="topbar">Class Schedules</div>

    <!-- Section -->
    <div class="section">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h3>Schedule List</h3>
        <div style="display:flex; gap:10px; align-items:center;">
          <input type="text" id="searchInput" placeholder="Search schedule..." style="padding:8px; border:1px solid #ccc; border-radius:5px; width:200px;">
          <select id="gradeFilter" style="padding:8px 12px; height:36px; border:1px solid #ccc; border-radius:5px;">
            <option value="">All Grades</option>
            <option value="11">Grade 11</option>
            <option value="12">Grade 12</option>
          </select>
          <select id="strandFilter" style="padding:8px 12px; height:36px; border:1px solid #ccc; border-radius:5px;">
            <option value="">All Strand</option>
            <option value="GAS">GAS</option>
            <option value="HUMSS">HUMSS</option>
            <option value="STEM">STEM</option>
            <option value="ABM">ABM</option>
          </select>
          <button id="exportExcel" style="background:#004aad; color:white; border-radius:5px; padding:8px 12px; display:flex; align-items:center; cursor:pointer; border:none;">
            <span class="material-icons" style="margin-right:5px;">table_view</span> XLSX
          </button>
          <button id="exportPDF" style="background:#004aad; color:white; border-radius:5px; padding:8px 12px; display:flex; align-items:center; cursor:pointer; border:none;">
            <span class="material-icons" style="margin-right:5px;">picture_as_pdf</span> PDF
          </button>
        </div>
      </div>

      <!-- Table -->
      <table id="courseTable" style="width:100%; border-collapse:collapse; margin-top:15px;">
        <thead style="background-color:#004aad; color:white;">
          <tr>
            <th>Course Code</th>
            <th>Subject</th>
            <th>Teacher</th>
            <th>Grade</th>
            <th>Strand</th>
            <th>Section</th>
            <th>Schedule</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="courseBody">
          <!-- populated by AJAX -->
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modals (Add/Edit) -->
  <div id="editModal" class="modal">
    <div class="modal-content">
      <h2>Edit Schedule</h2>
      <form id="editForm">
        <input type="hidden" id="editCourseCode" name="course_id">
        <input type="text" id="editSubjectName" name="course_name" placeholder="Subject">
        <input type="text" id="editTeacher" name="prof_name" placeholder="Teacher">
        <input type="text" id="editGrade" name="grade" placeholder="Grade">
        <input type="text" id="editStrand" name="strand" placeholder="Strand">
        <input type="text" id="editSection" name="section" placeholder="Section">
        <input type="text" id="editDay" name="day" placeholder="Day">
        <input type="text" id="editTimeStart" name="time_start" placeholder="Start Time">
        <input type="text" id="editTimeEnd" name="time_end" placeholder="End Time">
        <button type="submit">Update</button>
        <button type="button" onclick="closeEditModal()">Cancel</button>
      </form>
    </div>
  </div>

  <script>
    let courseTable;

    function fetchCourses() {
      const strand = $('#strandFilter').val();
      const grade = $('#gradeFilter').val();
      const search = $('#searchInput').val();

      $.ajax({
        url:'/CAPSTONE_LMS_EHS/api/admin/admin_course/get_courses.php',
        type:'GET',
        data:{strand, grade, search},
        success:function(data){
          $('#courseBody').html(data);

          // Destroy DataTable if exists
          if($.fn.DataTable.isDataTable('#courseTable')){
            $('#courseTable').DataTable().destroy();
          }

          // Only initialize DataTable if there are rows
          if($('#courseBody tr').length && !$('#courseBody tr td').attr('colspan')){
            courseTable = $('#courseTable').DataTable({
              paging:true,
              searching:false,
              ordering:true,
              dom:'Bfrtip',
              buttons:[]
            });
          }
        }
      });
    }

    $(document).ready(function(){
      fetchCourses();

      $('#strandFilter, #gradeFilter').on('change', fetchCourses);
      $('#searchInput').on('keyup', fetchCourses);

      $('#courseTable').on('click', '.edit-btn', function(){
        const courseId = $(this).closest('tr').find('td:first').text().trim();
        openEditModal(courseId);
      });

      $('#courseTable').on('click', '.delete-btn', function(){
        const courseId = $(this).closest('tr').find('td:first').text().trim();
        deleteCourse(courseId);
      });

      $('#exportExcel').on('click', function(){
        courseTable.button().add(0,{extend:'excelHtml5',title:'Course_Schedule_Export'});
        courseTable.button(0).trigger();
        courseTable.buttons().remove();
      });

      $('#exportPDF').on('click', function(){
        courseTable.button().add(0,{extend:'pdfHtml5',title:'Course_Schedule_Export',orientation:'landscape',pageSize:'A4'});
        courseTable.button(0).trigger();
        courseTable.buttons().remove();
      });
    });

    function openEditModal(courseId){
      $.ajax({
        url:'/CAPSTONE_LMS_EHS/api/admin/admin_course/get_courses_details.php',
        type:'GET',
        data:{course_id:courseId},
        dataType:'json',
        success:function(data){
          if(data.error){ alert(data.error); return; }
          $('#editModal').addClass('modal-visible');
          $('#editCourseCode').val(data.course_id);
          $('#editSubjectName').val(data.course_name);
          $('#editTeacher').val(data.prof_name);
          $('#editGrade').val(data.grade);
          $('#editStrand').val(data.strand);
          $('#editSection').val(data.section);
          $('#editDay').val(data.day);
          $('#editTimeStart').val(data.time_start);
          $('#editTimeEnd').val(data.time_end);
        },
        error:function(){ alert('Failed to fetch course details.'); }
      });
    }

    function closeEditModal(){ $('#editModal').removeClass('modal-visible'); }

    $('#editForm').on('submit',function(e){
      e.preventDefault();
      const formData = new FormData(this);
      $.ajax({
        url:'/CAPSTONE_LMS_EHS/api/admin/admin_course/edit_courses.php',
        type:'POST',
        data:formData,
        processData:false,
        contentType:false,
        success:function(msg){ alert(msg); closeEditModal(); fetchCourses(); }
      });
    });

    function deleteCourse(courseId){
      if(!confirm('Are you sure you want to delete this schedule?')) return;
      $.post('/CAPSTONE_LMS_EHS/api/admin/admin_course/delete_courses.php', {course_id:courseId}, function(msg){
        alert(msg);
        fetchCourses();
      });
    }
  </script>
</body>
</html>

<?php $conn=null; ?>
