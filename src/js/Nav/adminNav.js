

navContainer = document.getElementById("NAV")
navContainer.innerHTML = NAV()


function NAV(){
    return(
    `
        <div class="container-fluid d-flex align-items-center justify-content-between py-2">

            <!-- LEFT SIDE -->
            <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="/">
                <img 
                    src="/src/assets/logo.png"
                    alt="Logo"
                    width="42"
                    height="42"
                    class="rounded-circle border border-light object-fit-cover"
                >

                <span class="fw-bold text-white fs-5">
                    EasyMarket
                </span>
            </a>

            <!-- RIGHT SIDE -->
            <div class="d-flex align-items-center gap-2">

                <!-- LOGIN BUTTON -->
                <a href="/src/pages/auth/logout.html" class="btn btn-danger rounded-pill px-4">                    
                    <i class="bi bi-box-arrow-right"></i>
                </a>

            </div>

        </div>
    `)
}