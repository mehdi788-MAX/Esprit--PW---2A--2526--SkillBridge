// Returns the CSS class for a test level
function levelClass(level) {
  if (level === 'Débutant') return 'debut';
  if (level === 'Moyen')    return 'moyen';
  return 'avance';
}

// Render test cards into the grid
function renderTests(list) {
  const grid = document.getElementById('tests-grid');

  if (list.length === 0) {
    grid.innerHTML = '<p class="text-center text-muted py-5">Aucun test trouvé.</p>';
    return;
  }

  grid.innerHTML = list.map(t => `
    <div class="col-md-6 col-lg-4">
      <div class="ef-card">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div class="ef-card-icon">📊</div>
          <span class="level-badge level-${levelClass(t.level)}">${t.level}</span>
        </div>
        <h3>${t.title}</h3>
        <p class="cat">${t.category}</p>
        <div class="skill-tags">
          ${t.skills.slice(0, 3).map(s => `<span class="skill-tag">${s}</span>`).join('')}
          ${t.skills.length > 3 ? `<span class="skill-tag" style="color:#9ca3af;">+${t.skills.length - 3}</span>` : ''}
        </div>
        <div class="d-flex justify-content-between text-muted mb-3" style="font-size:0.75rem;">
          <span>👥 ${t.certifiedCount} certifiés</span>
          <span>⏱ ${t.duration} min</span>
        </div>
        <div class="d-flex justify-content-between mb-1" style="font-size:0.72rem;">
          <span style="color:#9ca3af;text-transform:uppercase;letter-spacing:1px;font-weight:700;">Score Moyen</span>
          <span style="color:#e87532;font-weight:700;">${t.averageScore}%</span>
        </div>
        <div class="ef-bar"><div class="ef-bar-fill" style="width:${t.averageScore}%;"></div></div>
      </div>
    </div>
  `).join('');
}

// Filter tests based on search input and category
function filterTests() {
  const q   = document.getElementById('ef-search').value.toLowerCase();
  const cat = document.getElementById('ef-cat').value;

  const filtered = TESTS.filter(t => {
    const matchText = t.title.toLowerCase().includes(q) || t.skills.some(s => s.toLowerCase().includes(q));
    const matchCat  = cat === 'Tous' || t.category === cat;
    return matchText && matchCat;
  });

  renderTests(filtered);
}

// Render freelancer cards
function renderFreelancers() {
  document.getElementById('freelancers-grid').innerHTML = FREELANCERS.map(f => `
    <div class="col-md-6 col-lg-3">
      <div class="fl-card">
        <div class="fl-avatar-wrap">
          <div class="fl-avatar" style="background:${f.avatarColor};">👤</div>
          <div class="fl-check"><i class="bi bi-patch-check-fill"></i></div>
        </div>
        <h4>${f.name}</h4>
        <p class="fl-spec">${f.specialty}</p>
        <p class="fl-loc">📍 Tunisie</p>
        <div class="fl-score-box">
          <div class="sl">
            <span>Score Test</span>
            <span>${f.score}%</span>
          </div>
          <div class="fl-score-bar">
            <div class="fl-score-fill" style="width:${f.score}%;"></div>
          </div>
        </div>
        <button class="btn-profile">Voir le profil</button>
      </div>
    </div>
  `).join('');
}

// Run on page load
renderTests(TESTS);
renderFreelancers();
