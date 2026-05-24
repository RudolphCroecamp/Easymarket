

navContainer = document.getElementById("NAV")
navContainer.innerHTML = NAV()


function NAV(){
    return(
    `
    <div class="container-fluid px-4 d-flex flex-wrap align-items-center justify-content-between py-2">

        <!-- LOGO -->
        <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="/">
            <img 
                src="/src/images/logo.png"
                alt="Logo"
                width="38"
                height="38"
                class="rounded-circle object-fit-cover"
            >
            <span class="fw-bold text-dark fs-5">
                EasyMarket
            </span>
        </a>

        <!-- RIGHT MENU -->
        <div class="d-flex align-items-center gap-2 order-1 order-xl-3 ms-1">

            <!-- MESSAGES -->
            <button
                class="btn btn-light d-flex align-items-center gap-2 rounded-pill px-3"
                data-bs-toggle="offcanvas"
                data-bs-target="#contactPanel"
            >
                <i class="bi bi-chat-dots"></i>
                <span class="d-none d-lg-inline">
                    Messages
                </span>
            </button>

            <!-- MENU -->
            <div class="dropdown">
                <button 
                    class="btn btn-light rounded-circle"
                    data-bs-toggle="dropdown"
                >
                    <i class="bi bi-list fs-5"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3">
                    <li><a class="dropdown-item" href="/src/pages/listings/new-lisiting.html">New Listings</a></li>
                    <li><a class="dropdown-item" href="/src/pages/listings/view-listings.html">View Listings</a></li>
                    <li><a class="dropdown-item" href="/src/pages/settings/settings.html">Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    
                    <li><a class="dropdown-item" href="/src/pages/payments/cart.html">Cart</a></li>
                    <li><hr class="dropdown-divider"></li>

                    <li><a class="dropdown-item" href="/src/pages/payments/payment.html">Checkout</a></li>
                    <li><hr class="dropdown-divider"></li>

                    <li><a class="dropdown-item text-danger" href="/src/pages/auth/logout.html">Logout</a></li>
                </ul>
            </div>

        </div>

        <!-- SEARCH -->
        <div class="w-100 w-lg-auto flex-grow-1 mx-lg-5 mt-3 mt-lg-0 order-2 order-xl-2" style="max-width: 700px;">
            <div class="search-wrapper">
                <input 
                    class="form-control border-0 shadow-none"
                    id="searchBox"
                    type="text"
                    placeholder="Search products..."
                >
                <button 
                    class="search-btn"
                    id="searchBtn"
                >
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>

    </div>
    `)
}