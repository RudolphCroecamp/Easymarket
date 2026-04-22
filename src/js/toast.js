export default function showToast(message, type = "dark") {
    const toastEl = document.getElementById("reviewToast");
    const toastMsg = document.getElementById("toastMessage");

    // Change message
    toastMsg.textContent = message;

    toastEl.className = `toast align-items-center text-bg-${type} border-0`;

    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}