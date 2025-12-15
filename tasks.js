
const categorySelect = document.getElementById('category');
const customCatInput = document.getElementById('custom_category');

function updateCustomCategoryVisibility() { //Function for created custom category
    if (!categorySelect || !customCatInput) return;
    if (categorySelect.value === '__custom') { //If custom was chosen in dropdown
        customCatInput.style.display = 'block';
        customCatInput.focus();
    } else {
        customCatInput.style.display = 'none'; //If not chosen, hide the textbox
        customCatInput.value = '';
    }
}

if (categorySelect && customCatInput) {
    categorySelect.addEventListener('change', updateCustomCategoryVisibility);
    updateCustomCategoryVisibility();
}

//Uses AJAX to prevent refresh when navigating through calendar
const calendarWrapper = document.querySelector('.calendar-wrapper');
const categoryFilter = document.querySelector('.filter-form select[name="category"]');
const statusFilter = document.querySelector('.filter-form select[name="status_filter"]');

function attachCalendarNavHandlers() {
    if (!calendarWrapper) return;
    const navButtons = calendarWrapper.querySelectorAll('.calendar-nav-btn');

    navButtons.forEach((btn) => {
        btn.addEventListener('click', function () {
            const year = this.getAttribute('data-year');
            const month = this.getAttribute('data-month');
            const category = categoryFilter ? categoryFilter.value : 'all';
            const status = statusFilter ? statusFilter.value : 'all';

            const url = `tasks.php?year=${year}&month=${month}` + `&category=${encodeURIComponent(category)}` + `&status_filter=${encodeURIComponent(status)}` + `&ajax=calendar`;

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then((resp) => resp.text())
                .then((html) => {
                    calendarWrapper.innerHTML = html;
                    attachCalendarNavHandlers();
                })
                .catch((err) => console.error('Calendar load error:', err));
        });
    });
}

attachCalendarNavHandlers();