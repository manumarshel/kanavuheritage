document.addEventListener("DOMContentLoaded", () => {
  const bookingForm = document.getElementById("bookingForm");
  const statusBox = document.getElementById("statusBox");
  const submitBtn = document.getElementById("submitBtn");

  if (!bookingForm) return;

  const showStatus = (type, msg) => {
    if (!statusBox) return;
    statusBox.className = `alert alert-${type}`;
    statusBox.textContent = msg;
    statusBox.style.display = "block";
  };

  const toYmd = (value) => {
    const v = String(value || "").trim();
    // already yyyy-mm-dd
    if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return v;
    // dd-mm-yyyy
    const m1 = v.match(/^(\d{2})-(\d{2})-(\d{4})$/);
    if (m1) return `${m1[3]}-${m1[2]}-${m1[1]}`;
    // dd/mm/yyyy
    const m2 = v.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (m2) return `${m2[3]}-${m2[2]}-${m2[1]}`;
    return v;
  };

  bookingForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = "Checking availability...";
    }
    showStatus("info", "Checking availability...");

    try {
      // Build payload from the form
      const fd = new FormData(bookingForm);
      fd.set("mode", "check");
      if (fd.has("check_in")) fd.set("check_in", toYmd(fd.get("check_in")));

      const res1 = await fetch(bookingForm.action, {
        method: "POST",
        body: fd,
        headers: { "Accept": "application/json" },
      });
      const data1 = await res1.json().catch(() => ({}));

      if (!res1.ok || !data1.ok) {
        throw new Error(data1.message || `Availability check failed (${res1.status})`);
      }

      // Availability ok -> create booking & start payment
      if (submitBtn) submitBtn.textContent = "Starting payment...";
      showStatus("success", "Dates are available. Redirecting to payment...");

      const fd2 = new FormData(bookingForm);
      fd2.set("mode", "create");
      if (fd2.has("check_in")) fd2.set("check_in", toYmd(fd2.get("check_in")));

      const res2 = await fetch(bookingForm.action, {
        method: "POST",
        body: fd2,
        headers: { "Accept": "application/json" },
      });
      const data2 = await res2.json().catch(() => ({}));

      if (!res2.ok || !data2.ok) {
        throw new Error(data2.message || `Booking failed (${res2.status})`);
      }

      if (data2.redirect_url) {
        window.location.href = data2.redirect_url;
        return;
      }

      throw new Error("Booking created, but redirect_url was missing.");
    } catch (err) {
      console.error(err);
      showStatus("danger", err?.message || "Something went wrong. Please try again.");
      if (submitBtn) submitBtn.disabled = false;
      if (submitBtn) submitBtn.textContent = "Submit Booking";
    }
  });
});
