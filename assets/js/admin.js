// Returns the SB Admin pill class for a test level
function levelPill(level) {
  if (level === 'Débutant') return 'success';
  if (level === 'Moyen')    return 'warning';
  return 'danger';
}

// Render the tests table
function renderAdminTests() {
  document.getElementById('admin-tests-table').innerHTML = TESTS.map(t => `
    <tr>
      <td class="td-bold">${t.title}</td>
      <td>${t.category}</td>
      <td>${t.duration} min</td>
      <td><span class="sb-pill ${levelPill(t.level)}">${t.level}</span></td>
      <td>${t.certifiedCount}</td>
      <td>
        <button class="sb-icon-btn edit me-1"><i class="bi bi-pencil"></i></button>
        <button class="sb-icon-btn del"><i class="bi bi-trash"></i></button>
      </td>
    </tr>
  `).join('');
}

// Render the freelancer results table
function renderAdminResults() {
  document.getElementById('admin-results-table').innerHTML = FREELANCERS.map(f => {
    const color       = f.score >= 50 ? '#1cc88a' : '#e74a3b';
    const statusClass = f.status === 'Certifié' ? 'success' : 'danger';

    return `
      <tr>
        <td class="td-bold">${f.name}</td>
        <td>${f.testName}</td>
        <td>${f.date}</td>
        <td>
          <div class="d-flex align-items-center gap-2">
            <div class="mini-bar">
              <div class="mini-fill" style="width:${f.score}%; background:${color};"></div>
            </div>
            <span style="font-size:0.75rem; font-weight:700; color:${color};">${f.score}%</span>
          </div>
        </td>
        <td><span class="sb-pill ${statusClass}">${f.status}</span></td>
        <td><button class="sb-icon-btn view"><i class="bi bi-eye"></i></button></td>
      </tr>
    `;
  }).join('');
}

// Show or hide the "new test" form
function toggleForm() {
  document.getElementById('sb-form').classList.toggle('open');
}

// Run on page load
renderAdminTests();
renderAdminResults();
