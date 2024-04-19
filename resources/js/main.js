
htmx.on("showToast", function (event) {

    let toast = document.getElementById('toast');

    if (event.detail.value) {
        toast.classList.remove("bg-success");
        toast.classList.add(event.detail.value);
        toast.addEventListener('hidden.bs.toast', (event) => {
            toast.classList.add("bg-success");
            toast.classList.remove(event.detail.value);
        })

    }

    let toastInstance = new bootstrap.Toast(toast);
    toastInstance.show();
    window.location.reload();

});



