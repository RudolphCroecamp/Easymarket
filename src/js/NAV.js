

navContainer = document.getElementById("NAV")
navContainer.innerHTML = NAV()


function NAV(){
    return(
    `
        <div class="container-fluid flex-column">

        <!-- TOP BAR -->
        <div class="w-100 d-flex align-items-center justify-content-between py-2">

            <!-- LEFT -->
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

                <!-- ✅ MESSAGES (ALWAYS OUTSIDE BURGER) -->
                <button 
                    class="btn btn-outline-light d-flex align-items-center gap-1"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#contactPanel"
                >
                    <i class="bi bi-chat-dots-fill"></i>
                    <span class="d-none d-sm-inline">Messages</span>
                </button>

                <!-- ☰ BURGER DROPDOWN -->
                <div class="dropdown">

                    <button 
                        class="navbar-toggler border-0 shadow-none"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                    >
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <!-- MENU -->
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg border-0 mt-3">

                        <li>
                            <a class="dropdown-item" href="/src/pages/listings/new-lisiting.html">
                                New Listing
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="/src/pages/listings/view-listings.html">
                                View Listings
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <a class="dropdown-item" href="/src/pages/settings/settings.html">
                                Settings
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <a class="dropdown-item text-danger" href="/src/pages/auth/logout.html">
                                Logout
                            </a>
                        </li>

                    </ul>

                </div>

            </div>
        </div>

        <!-- SEARCH -->
        <div class="w-100 pb-3">
            <div class="d-flex col-12 col-sm-10 col-md-7 col-lg-5 mx-auto mt-2">

                <input 
                    class="form-control me-2 rounded-3 px-3"
                    id="searchBox"
                    type="text"
                    placeholder="Search..."
                >

                <button 
                    class="btn btn-primary rounded-3 px-4"
                    id="searchBtn"
                >
                    Search
                </button>

            </div>
        </div>

    </div>
    `)
}