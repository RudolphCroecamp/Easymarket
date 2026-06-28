

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
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-dots" viewBox="0 0 16 16">
                    <path d="M5 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
                    <path d="m2.165 15.803.02-.004c1.83-.363 2.948-.842 3.468-1.105A9 9 0 0 0 8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6a10.4 10.4 0 0 1-.524 2.318l-.003.011a11 11 0 0 1-.244.637c-.079.186.074.394.273.362a22 22 0 0 0 .693-.125m.8-3.108a1 1 0 0 0-.287-.801C1.618 10.83 1 9.468 1 8c0-3.192 3.004-6 7-6s7 2.808 7 6-3.004 6-7 6a8 8 0 0 1-2.088-.272 1 1 0 0 0-.711.074c-.387.196-1.24.57-2.634.893a11 11 0 0 0 .398-2"/>
                </svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
                    </svg>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-3">
                    <li><a class="dropdown-item" href="/src/pages/listings/new-lisiting.html">New Listings</a></li>
                    <li><a class="dropdown-item" href="/src/pages/listings/view-listings.html">View Listings</a></li>
                    <li><a class="dropdown-item disabled"  href="/src/pages/settings/settings.html">Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    
                    <li><a class="dropdown-item" href="/src/pages/payments/cart.html">Cart</a></li>
                    <li><hr class="dropdown-divider"></li>

                    <li><a class="dropdown-item" href="/src/pages/payments/payment.html">Checkout</a></li>
                    <li><hr class="dropdown-divider"></li>

                    <li><a class="dropdown-item" href="/src/pages/orders/buyerOrders.html?page=1">Orders Placed</a></li>
                    <li><a class="dropdown-item" href="/src/pages/orders/sellerOrders.html?page=1">Orders Recieved</a></li>
                    <li><hr class="dropdown-divider"></li>

                    <li><a class="dropdown-item text-danger" href="/src/pages/auth/logout.html">Logout</a></li>
                </ul>
            </div>

        </div>

        <!-- SEARCH -->
        <div class="w-100 w-lg-auto flex-grow-1 mx-lg-5 mt-3 mt-lg-0 order-2 order-xl-2" style="max-width: 700px;">
            <div class="search-wrapper">
                <input 
                    class="search-input"
                    id="searchBox"
                    type="text"
                    placeholder="Search products..."
                >
                <button 
                    class="search-btn"
                    id="searchBtn"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-search">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                    </svg>
                </button>
            </div>
        </div>

    </div>
    `)
}