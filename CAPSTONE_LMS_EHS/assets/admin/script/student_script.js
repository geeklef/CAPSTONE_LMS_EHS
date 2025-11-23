// 🔹 Fetch students from API
function fetchStudents() {
  const strand = document.getElementById('strandFilter').value;
  const grade = document.getElementById('gradeFilter').value;
  const search = document.getElementById('searchInput').value;

  fetch(`/CAPSTONE_LMS_EHS/api/admin/admin_student/get_students.php?strand=${strand}&grade=${grade}&search=${search}`)
    .then(response => response.text())
    .then(data => {
      document.getElementById('studentBody').innerHTML = data;
    });
}

// 🔹 Event listeners
document.getElementById('strandFilter').addEventListener('change', fetchStudents);
document.getElementById('gradeFilter').addEventListener('change', fetchStudents);
document.getElementById('searchInput').addEventListener('keyup', fetchStudents);

// Initial load
window.onload = fetchStudents;

// Strand Filter
document.getElementById('strandFilter').addEventListener('change', function () {
  const selectedStrand = this.value.trim();
  const rows = document.querySelectorAll('table tbody tr');
  rows.forEach(row => {
    const strandCell = row.children[3];
    if (!strandCell) return;
    const strand = strandCell.textContent.trim();
    row.style.display = !selectedStrand || strand === selectedStrand ? '' : 'none';
  });
});



// ADD STUDENT MODAL
document.getElementById('openAddModal').addEventListener('click', function () {
  document.getElementById('addStudentModal').classList.add('modal-visible');
});

function closeAddModal() {
  document.getElementById('addStudentModal').classList.remove('modal-visible');
}

// ---------- Modal Controls ----------
function openManualAdd() {
  document.getElementById('addStudentModal').classList.remove('modal-visible');
  document.getElementById('manualAddModal').classList.add('modal-visible');
}
function closeManualAdd() {
  document.getElementById('manualAddModal').classList.remove('modal-visible');
}

function openUploadModal() {
  document.getElementById('addStudentModal').classList.remove('modal-visible');
  document.getElementById('uploadModal').classList.add('modal-visible');
}
function closeUploadModal() {
  document.getElementById('uploadModal').classList.remove('modal-visible');
}

// ---------- Add Student via AJAX ----------
document.getElementById('addStudentForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch('/CAPSTONE_LMS_EHS/api/admin/admin_student/add_students.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(data => {
    alert(data);
    closeManualAdd();
    fetchStudents(); // refresh table
  })
  .catch(err => console.error(err));
});


// When you click the edit icon
document.addEventListener("click", function (e) {
  const editButton = e.target.closest(".edit-btn");
  if (!editButton) return;

  const row = editButton.closest("tr");
  if (!row) return;

  // Get student ID from first column
  const studId = row.querySelector("td").textContent.trim();
  console.log("🧩 Editing student ID:", studId);

  // Fetch student details from Supabase (via your PHP API)
  fetch(`/CAPSTONE_LMS_EHS/api/admin/admin_student/get_student_details.php?stud_id=${studId}`)
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        alert("❌ " + data.error);
        return;
      }

      // Fill modal fields with fetched data
      document.getElementById("editStudId").value = data.stud_id;
      document.getElementById("editStudUser").value = data.stud_user;
      document.getElementById("editPassword").value = ""; // don’t autofill for security
      document.getElementById("editFirstName").value = data.first_name;
      document.getElementById("editLastName").value = data.last_name;
      document.getElementById("editEmail").value = data.email;
      document.getElementById("editGrade").value = data.grade_level;
      document.getElementById("editStrand").value = data.strand;
      document.getElementById("editSection").value = data.section;
      document.getElementById("editStatus").value = data.status;

      // Show modal
      document.getElementById("editModal").classList.add("modal-visible");
    })
    .catch(err => {
      console.error("⚠️ Fetch error:", err);
      alert("Failed to fetch student data. Check console.");
    });
});

// Close modal
function closeEditModal() {
  document.getElementById("editModal").classList.remove("modal-visible");
}

// Handle submit for edit form
document.getElementById("editForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  fetch("/CAPSTONE_LMS_EHS/api/admin/admin_student/edit_students.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.text())
  .then(msg => {
    alert(msg);
    closeEditModal();
    location.reload(); // refresh table
  })
  .catch(err => console.error("Edit error:", err));
});

// ---------- SUBMIT EDIT FORM ----------
document.getElementById("editForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const formData = new FormData(this);

  fetch("/CAPSTONE_LMS_EHS/api/admin/admin_student/edit_students.php", {
    method: "POST",
    body: formData
  })
    .then(res => res.text())
    .then(msg => {
      alert(msg);
      closeEditModal();
      fetchStudents(); // refresh table after update
    })
    .catch(err => console.error("Edit error:", err));
});


// Delete Student
function deleteStudent(stud_id) {
  if (!confirm("Are you sure you want to delete this student?")) return;

  const formData = new FormData();
  formData.append("stud_id", stud_id);

  fetch("/CAPSTONE_LMS_EHS/api/admin/admin_student/delete_students.php", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.text())
    .then((msg) => {
      alert(msg);
      fetchStudents();
    });
}



