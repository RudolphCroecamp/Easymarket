

navContainer = document.getElementById("NAV")
navContainer.innerHTML = NAV()


function NAV(){
    return(
    `
    <div class="container-fluid d-flex flex-wrap row">
        <a class="navbar-brand col-6 col-sm-6" href="/">Logo</a>

        <button class="navbar-toggler col-2 col-sm-5 me-2" type="button" data-bs-toggle="collapse" data-bs-target="#mynavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse col-2 col-sm-5" id="mynavbar">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link btn-link active" href="/">Home</a>
                </li>

                <!-- Trigger (Contact Item) -->
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="offcanvas" data-bs-target="#contactPanel">
                        Open Contact
                    </button>
                </li>

                <!-- Trigger (listing dropdown) -->
                <li class="dropdown">
                    <button class="nav-link btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        my listings
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/src/pages/listings/view-listings.html">view listings</a></li>
                        <li><a class="dropdown-item" href="/src/pages/listings/new-lisiting.html">New listing</a></li>
                        <li><hr class="dropdown-divider "></li>
                        <li><a class="dropdown-item" href="/src/pages/listings/view-listings.html">Remove listing</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/src/pages/auth/logout.html">logout</a>
                </li>
            </ul>
        </div>

        <div class="d-flex col-12 col-sm-10 col-md-6 col-lg-4 mt-2">
            <input class="form-control me-2" id="searchBox" type="text" placeholder="Search">
            <button class="btn btn-primary" id="searchBtn">Search</button>
        </div>

    </div>
    `)
}