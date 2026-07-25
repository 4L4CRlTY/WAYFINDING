<div class="leftside-menu">
    <!-- Brand Logo Light -->
    <a href="index.php" class="logo logo-light">
        <span class="logo-lg">
            <img src="assets/images/logo-light.png" alt="logo" />
        </span>
        <span class="logo-sm">
            <img src="assets/images/slsu-sm.png" alt="small logo" />
        </span>
    </a>

    <!-- Brand Logo Dark -->
    <a href="index.php" class="logo logo-dark">
        <span class="logo-lg">
            <img src="assets/images/slsu-logo.png" alt="dark logo" />
        </span>
        <span class="logo-sm">
            <img src="assets/images/slsu-sm.png" alt="small logo" />
        </span>
    </a>

    <!-- Sidebar Hover Menu Toggle Button -->
    <div class="button-sm-hover" data-bs-toggle="tooltip" data-bs-placement="right" title="Show Full Sidebar">
        <i class="ri-checkbox-blank-circle-line align-middle"></i>
    </div>

    <!-- Full Sidebar Menu Close Button -->
    <div class="button-close-fullsidebar">
        <i class="ri-close-fill align-middle"></i>
    </div>

    <!-- Sidebar -left -->
    <div class="h-100" id="leftside-menu-container" data-simplebar>
        <!-- Leftbar User -->
        <div class="leftbar-user">
            <a href="pages-profile.php">
                <img src="assets/images/users/avatar-1.jpg" alt="user-image" height="42"
                    class="rounded-circle shadow-sm" />
                <span class="leftbar-user-name mt-2">Tosha Minner</span>
            </a>
        </div>

        <!--- Sidemenu -->
        <ul class="side-nav">
            <li class="side-nav-title">Navigation</li>

            <li class="side-nav-item">
                <a href="{{ route('admin.dashboard') }}" class="side-nav-link">
                    <i class="ri-home-4-line"></i>

                    <span> Dashboards </span>
                </a>
            </li>

            <li class="side-nav-title">Campus Event</li>

            <li class="side-nav-item">
                <a href="{{ route('admin.campus-event') }}" class="side-nav-link">
                    <i class="ri-megaphone-line"></i>

                    <span> Campus Event </span>
                </a>
            </li>

            <li class="side-nav-title">OutDoor</li>

            <li class="side-nav-item">
                <a href="{{ route('admin.buildings') }}" class="side-nav-link">
                    <i class="ri-building-2-line"></i>
                    <span> Buildings </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.path') }}" class="side-nav-link">
                    <i class="ri-route-line"></i>
                    <span> Path </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.hazard-point') }}" class="side-nav-link">
                    <i class="ri-error-warning-line"></i>
                    <span> Hazard Point </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.entry-point') }}" class="side-nav-link">
                    <i class="ri-map-pin-add-line"></i>
                    <span> Entry Point </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.building-entrances') }}" class="side-nav-link">
                    <i class="ri-door-open-line"></i>
                    <span> Building Entrance </span>
                </a>
            </li>

            <li class="side-nav-title">LandUse</li>

            <li class="side-nav-item">
                <a href="{{ route('admin.landuse') }}" class="side-nav-link">
                    <i class="ri-earth-line"></i>
                    <span> LandUse </span>
                </a>
            </li>

            <li class="side-nav-title">Indoor</li>

            <li class="side-nav-item">
                <a href="{{ route('admin.indoor-map') }}" class="side-nav-link">
                    <i class="ri-map-2-line"></i>
                    <span> Indoor Map </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.indoor-path') }}" class="side-nav-link">
                    <i class="ri-route-line"></i>
                    <span> Indoor Path </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.indoor-room') }}" class="side-nav-link">
                    <i class="ri-door-line"></i>
                    <span> Indoor Room </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.indoor-entrances') }}" class="side-nav-link">
                    <i class="ri-door-open-line"></i>
                    <span> Indoor Entrance </span>
                </a>
            </li>

            <li class="side-nav-title">LINKS</li>

            <li class="side-nav-item">
                <a href="{{ route('admin.indoor-stairs-link') }}" class="side-nav-link">
                    <i class="ri-arrow-up-down-line"></i>
                    <span> Indoor Stairs Link </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.building-entrance-link') }}" class="side-nav-link">
                    <i class="ri-link-m"></i>
                    <span> Building Entrance Link </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.destination-keyword') }}" class="side-nav-link">
                    <i class="ri-key-2-line"></i>
                    <span> Destination Keyword </span>
                </a>
            </li>





        </ul>
        <!--- End Sidemenu -->

        <div class="clearfix"></div>
    </div>
</div>
