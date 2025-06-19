// Card scroll and pagination
function scrollToCard(index) {
  const cards = document.getElementById('cards');
  const cardWidth = cards.children[0].offsetWidth + 20; // Card width + gap
  cards.scrollTo({
    left: cardWidth * index,
    behavior: 'smooth'
  });

  // Update active class
  document.querySelectorAll('.pagination span').forEach((el, i) => {
    el.classList.toggle('active', i === index);
  });
}

function pad2(n) { return n < 10 ? '0' + n : n; }
function updateSimpleClock() {
  const now = new Date();
  document.getElementById('clock-hour').textContent = pad2(now.getHours());
  document.getElementById('clock-min').textContent = pad2(now.getMinutes());
  document.getElementById('clock-sec').textContent = pad2(now.getSeconds());
}
function updateSimpleCalendar() {
  const now = new Date();
  const day = now.getDate();
  const month = now.toLocaleString('default', { month: 'long' });
  const year = now.getFullYear();
  document.getElementById('simple-calendar').textContent = `${day} ${month} ${year}`;
}
setInterval(updateSimpleClock, 1000);
updateSimpleClock();
updateSimpleCalendar();

// Mini Calendar Widget
(function() {
  const calendarTitle = document.querySelector('.calendar-title');
  const calendarDates = document.querySelector('.calendar-dates');
  const prevBtn = document.querySelector('.calendar-prev');
  const nextBtn = document.querySelector('.calendar-next');
  let current = new Date();
  function renderCalendar(date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    const today = new Date();
    calendarTitle.textContent = date.toLocaleString('default', { month: 'long', year: 'numeric' });
    const firstDay = new Date(year, month, 1).getDay();
    const lastDate = new Date(year, month + 1, 0).getDate();
    let html = '';
    for (let i = 0; i < firstDay; i++) html += '<div></div>';
    for (let d = 1; d <= lastDate; d++) {
      const isToday = d === today.getDate() && month === today.getMonth() && year === today.getFullYear();
      html += `<div class="${isToday ? 'today' : ''}">${d}</div>`;
    }
    calendarDates.innerHTML = html;
  }
  prevBtn.onclick = () => { current.setMonth(current.getMonth() - 1); renderCalendar(current); };
  nextBtn.onclick = () => { current.setMonth(current.getMonth() + 1); renderCalendar(current); };
  renderCalendar(current);
})(); 