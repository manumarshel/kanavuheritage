// Highlight hovered menu item
let list = document.querySelectorAll(".navigation li");

function activeLink() {
  list.forEach((item) => {
    item.classList.remove("hovered");
  });
  this.classList.add("hovered");
}

list.forEach((item) => item.addEventListener("mouseover", activeLink));

// Menu Toggle
let toggle = document.querySelector(".toggle");
let navigation = document.querySelector(".navigation");
let main = document.querySelector(".main");

toggle.onclick = function () {
  navigation.classList.toggle("active");
  main.classList.toggle("active");
};

// User Dropdown Toggle
const userDropdown = document.getElementById("dropdownMenu");
const userIcon = document.querySelector(".user-dropdown ion-icon");

userIcon.onclick = function (event) {
  event.stopPropagation(); // Prevent window click from closing immediately
  userDropdown.style.display = userDropdown.style.display === "block" ? "none" : "block";
};

// Close dropdown if clicked outside
window.onclick = function (event) {
  if (!event.target.closest(".user-dropdown")) {
    userDropdown.style.display = "none";
  }
};


document.querySelectorAll('[data-toggle="collapse"]').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const group = btn.closest('.menu-group');
    group.classList.toggle('open');
  });
});
// /dashboard/js/main.js
(function () {
  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    // Log so we know the file actually loaded on each page
    // (Open DevTools Console: you should see this line once)
    // console.log('main.js loaded');

    var groups = document.querySelectorAll('.menu-group');
    groups.forEach(function (group) {
      var btn = group.querySelector('.menu-item.has-children[data-toggle="collapse"]');
      if (!btn) return;

      // find the submenu inside this group
      var submenu = group.querySelector('.submenu');
      if (!submenu) return;

      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var nowOpen = !group.classList.contains('open');
        group.classList.toggle('open', nowOpen);
        btn.setAttribute('aria-expanded', nowOpen ? 'true' : 'false');
      });
    });
  });
})();

