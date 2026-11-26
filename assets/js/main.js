document.addEventListener('DOMContentLoaded', function () {
  var t = document.getElementById('menuToggle'), s = document.getElementById('sidebar');
  if (t && s) t.addEventListener('click', function () { s.classList.toggle('open'); });

  // Client-side table search + filter: <input data-table-search="#tbl"> and <select data-table-filter="#tbl" data-col="2">
  function apply(tableSel) {
    var table = document.querySelector(tableSel); if (!table) return;
    var q = (document.querySelector('[data-table-search="' + tableSel + '"]') || {value: ''}).value.toLowerCase();
    var filters = document.querySelectorAll('[data-table-filter="' + tableSel + '"]');
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      var ok = tr.textContent.toLowerCase().indexOf(q) !== -1;
      filters.forEach(function (f) {
        if (f.value && tr.cells[+f.dataset.col] && tr.cells[+f.dataset.col].textContent.trim().indexOf(f.value) === -1) ok = false;
      });
      tr.style.display = ok ? '' : 'none';
    });
  }
  document.querySelectorAll('[data-table-search],[data-table-filter]').forEach(function (el) {
    var sel = el.dataset.tableSearch || el.dataset.tableFilter;
    el.addEventListener('input', function () { apply(sel); });
    el.addEventListener('change', function () { apply(sel); });
  });
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) { if (!confirm(el.dataset.confirm)) e.preventDefault(); });
  });
});
