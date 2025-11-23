function fetchTeachers() {
  const search = document.getElementById('searchInput').value;
  const dept = document.getElementById('departmentFilter').value;

  fetch(`../api/admin/admin_prof/get_prof.php?search=${search}&department=${dept}`)
    .then(response => response.text())
    .then(data => {
      document.getElementById('teacherBody').innerHTML = data;
    })
    .catch(error => {
      console.error("Fetch error:", error);
      document.getElementById('teacherBody').innerHTML = 
        `<tr><td colspan="5" style="color:red; text-align:center;">Failed to load data.</td></tr>`;
    });
}

    // === Event Listeners ===
    document.getElementById('searchInput').addEventListener('keyup', fetchTeachers);
    document.getElementById('departmentFilter').addEventListener('change', fetchTeachers);
    window.onload = fetchTeachers;

    // === Your existing modal scripts (unchanged) ===
    function openChoiceModal() { document.getElementById('choiceModal').classList.add('modal-visible'); }
    function closeChoiceModal() { document.getElementById('choiceModal').classList.remove('modal-visible'); }
    function openAddModal() { closeChoiceModal(); document.getElementById('addModal').classList.add('modal-visible'); }
    function closeAddModal() { document.getElementById('addModal').classList.remove('modal-visible'); }
    function openUploadModal() { closeChoiceModal(); document.getElementById('uploadModal').classList.add('modal-visible'); }
    function closeUploadModal() { document.getElementById('uploadModal').classList.remove('modal-visible'); }



//add
document.getElementById('addForm').addEventListener('submit', function (e) {
  e.preventDefault();

  const formData = new FormData(this);

  fetch('../api/admin/admin_prof/add_prof.php', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      alert(data.message);
      if (data.status === 'success') {
        closeAddModal();
        fetchTeachers(); // refresh table
      }
    })
    .catch(err => {
      console.error(err);
      alert('Error adding teacher.');
    });
});

//delete
function deleteProfessor(teacherId) {
  if (confirm("Are you sure you want to delete this professor?")) {
    fetch('../api/admin/admin_prof/delete_prof.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'teacher_id=' + encodeURIComponent(teacherId)
    })
    .then(response => response.text())
    .then(data => {
      alert(data);
      fetchProfessorData(); // refresh table
    })
    .catch(error => console.error('Error:', error));
  }
}
function openEditModal(teacherId) {
  fetch(`../api/admin/admin_prof/get_prof_details.php?teacher_id=${teacherId}`)
    .then(res => res.json())
    .then(data => {
      if (data.error) { alert(data.error); return; }

      document.getElementById('edit_teacher_id').value = data.teacher_id;
      document.getElementById('edit_teacher_user').value = data.teacher_user;
      document.getElementById('edit_password').value = data.teacher_pass;
      document.getElementById('edit_first_name').value = data.first_name;
      document.getElementById('edit_last_name').value = data.last_name;
      document.getElementById('edit_email').value = data.email;
      document.getElementById('edit_subject').value = data.subject_id;   // NEW
      document.getElementById('edit_department').value = data.department;

      document.getElementById('editModal').classList.add('modal-visible');
    })
    .catch(err => { console.error(err); alert('Failed to fetch professor details.'); });
}


// === CLOSE MODAL ===
function closeEditModal() {
  document.getElementById('editModal').classList.remove('modal-visible');
}

// === SAVE EDIT ===
function saveEdit() {
  const formData = new FormData(document.getElementById('editForm'));

  fetch('../api/admin/admin_prof/edit_prof.php', {
    method: 'POST',
    body: formData
  })
    .then(res => res.text())
    .then(data => {
      alert(data);
      closeEditModal();
      fetchTeachers();
    })
    .catch(err => {
      console.error(err);
      alert('Error saving edits.');
    });
}



