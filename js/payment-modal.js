document.addEventListener("DOMContentLoaded", function () {

  let bookingData = {};

  const form = document.getElementById("bookingForm");
  if (!form) {
    console.error("❌ bookingForm not found");
    return;
  }

  /* ================= FORM SUBMIT ================= */
  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const fd = new FormData(form);

    // 🔍 DEBUG: See exactly what FormData contains
    console.log("---- FORM DATA ----");
    for (let pair of fd.entries()) {
      console.log(pair[0], "=>", pair[1]);
    }

    bookingData = {
      patientName: fd.get("name"),
      patientEmail: fd.get("email"),
      patientPhone: fd.get("phone"),
      doctorId: fd.get("doctor_id"), // ✅ MUST come from <select name="doctor_id">
      appointmentDate: fd.get("date"),
      appointmentTime: fd.get("time"),
      sessionType: fd.get("session")
    };

    console.log("BOOKING DATA ✅", bookingData);

    // 🔒 Validation (prevents backend error)
    if (
      !bookingData.patientName ||
      !bookingData.patientEmail ||
      !bookingData.doctorId ||
      !bookingData.appointmentDate ||
      !bookingData.appointmentTime
    ) {
      alert("❌ Please fill all required fields");
      return;
    }

    // Open payment modal
    document.getElementById("payment-modal")
      .classList.remove("opacity-0", "pointer-events-none");
  });

  /* ================= FINISH PAYMENT ================= */
  window.finishPayment = async function () {

    const txnInput = document.getElementById("transaction-id");
    if (!txnInput) {
      alert("Transaction input not found");
      return;
    }

    const txn = txnInput.value.trim();
    if (!txn) {
      alert("Enter transaction ID");
      return;
    }

    bookingData.transactionId = txn;

    try {
      const res = await fetch("/api/create_booking.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(bookingData)
      });

      const data = await res.json();

      if (!data.success) {
        alert("❌ " + data.error);
        return;
      }

      alert("✅ Booking confirmed");
      window.location.reload();

    } catch (err) {
      console.error(err);
      alert("❌ Server error. Please try again.");
    }
  };

});
