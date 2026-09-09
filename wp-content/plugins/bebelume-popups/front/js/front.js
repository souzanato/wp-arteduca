/* Bebelume Popups – Frontend: open Bootstrap modals on load */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.bpp-modal').forEach(function (el) {
    var modal = new bootstrap.Modal(el);
    modal.show();

    // close-btn inside content closes the modal
    el.querySelectorAll('.close-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        modal.hide();
      });
    });
  });
});
