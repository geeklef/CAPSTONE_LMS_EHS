// =============================
// GLOBAL VARIABLES
// =============================
let courseTable;

// =============================
// FETCH COURSES
// =============================
function fetchCourses() {
  const strand = $('#strandFilter').val();
  const grade = $('#gradeFilter').val();
  const search = $('#searchInput').val();

  $.ajax({
    url: '/CAPSTONE_LMS_EHS/api/admin/admin_course/get_courses.php',
    type: 'GET',
    data: { strand, grade, search },
    success: function(data) {
      // Destroy previous DataTable if exists
      if ($.fn.DataTable.isDataTable('#courseTable')) {
        $('#courseTable').DataTable().destroy();
      }

      // Update table body
      $('#courseBody').html(data);

      // Reinitialize DataTable
      courseTable = $('#courseTable').DataTable({
        paging: true,
        searching: false, // use custom search
        ordering: true,
        dom: 'Bfrtip',
        buttons: []
      });
    },
    error: function(err) {
      console.error('Error fetching courses:', err);
    }
  });
}

// =============================
// EVENT LISTENERS
// =============================
$(document).ready(function() {
  // Initial fetch
  fetchCourses();

  // Filters
  $('#strandFilter, #gradeFilter').on('change', fetchCourses);
  $('#searchInput').on('keyup', fetchCourses);

  // Export buttons
  $('#exportExcel').on('click', function() {
    courseTable.button().add(0, { extend: 'excelHtml5', title: 'Course_Schedule_Export' });
    courseTable.button(0).trigger();
    courseTable.buttons().remove();
  });

  $('#exportPDF').on('click', function() {
    courseTable.button().add(0, { extend: 'pdfHtml5', title: 'Course_Schedule_Export', orientation: 'landscape', pageSize: 'A4' });
    courseTable.button(0).trigger();
    courseTable.buttons().remove();
  });

  // Delegated click for Edit buttons
  $('#courseTable').on('click', '.edit-btn', function() {
    const courseId = $(this).closest('tr').find('td:first').text().trim();
    openEditModal(courseId);
  });

  // Delegated click for Delete buttons
  $('#courseTable').on('click', '.delete-btn', function() {
    const courseId = $(this).closest('tr').find('td:first').text().trim();
    deleteCourse(courseId);
  });

  // Open Add Modal
  $('#openAddModal').on('click', function() {
    $('#addCourseModal').addClass('modal-visible');
  });
});

// =============================
// MODAL CONTROLS
// =============================
function closeAddModal() { $('#addCourseModal').removeClass('modal-visible'); }
function openManualAdd() { $('#addCourseModal').removeClass('modal-visible'); $('#manualAddModal').addClass('modal-visible'); }
function closeManualAdd() { $('#manualAddModal').removeClass('modal-visible'); }
function openUploadModal() { $('#addCourseModal').removeClass('modal-visible'); $('#uploadModal').addClass('modal-visible'); }
function closeUploadModal() { $('#uploadModal').removeClass('modal-visible'); }
function closeEditModal() { $('#editModal').removeClass('modal-visible'); }

// =============================
// ADD COURSE
// =============================
$('#addCourseForm').on('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);

  $.ajax({
    url: '/CAPSTONE_LMS_EHS/api/admin/admin_course/add_courses.php',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(msg) {
      alert(msg);
      closeManualAdd();
      fetchCourses();
    },
    error: function(err) {
      console.error('Error adding course:', err);
    }
  });
});

// =============================
// EDIT COURSE
// =============================
function openEditModal(courseId) {
  $.ajax({
    url: `/CAPSTONE_LMS_EHS/api/admin/admin_course/get_courses_details.php`,
    type: 'GET',
    data: { course_id: courseId },
    dataType: 'json',
    success: function(data) {
      if (data.error) {
        alert('❌ ' + data.error);
        return;
      }

      // Show modal
      $('#editModal').addClass('modal-visible');

      // Fill modal fields
      $('#editCourseCode').val(data.course_id || '');
      $('#editSubjectName').val(data.course_name || '');
      $('#editTeacher').val(data.prof_name || '');
      $('#editGrade').val(data.grade || '');
      $('#editStrand').val(data.strand || '');
      $('#editSection').val(data.section || '');
      $('#editDay').val(data.day || '');
      $('#editTimeStart').val(data.time_start || '');
      $('#editTimeEnd').val(data.time_end || '');
    },
    error: function(err) {
      console.error('Error fetching course details:', err);
      alert('Failed to fetch course details.');
    }
  });
}

$('#editForm').on('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);

  $.ajax({
    url: '/CAPSTONE_LMS_EHS/api/admin/admin_course/edit_courses.php',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(msg) {
      alert(msg);
      closeEditModal();
      fetchCourses();
    },
    error: function(err) {
      console.error('Error editing course:', err);
    }
  });
});

// =============================
// DELETE COURSE
// =============================
function deleteCourse(courseId) {
  if (!confirm('Are you sure you want to delete this schedule?')) return;

  $.ajax({
    url: '/CAPSTONE_LMS_EHS/api/admin/admin_course/delete_courses.php',
    type: 'POST',
    data: { course_id: courseId },
    success: function(msg) {
      alert(msg);
      fetchCourses();
    },
    error: function(err) {
      console.error('Error deleting course:', err);
    }
  });
}
