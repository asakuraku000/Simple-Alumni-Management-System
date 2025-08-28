<?php
    // Get the current file name
    $link = $_SERVER['PHP_SELF'];
    $link_array = explode('/', $link);
    $page = end($link_array);
?>

<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        <div class="logo-header" data-background-color="dark">
            <a href="dashboard.php" class="logo" style="color:white;">
                <img src="default/logo.png" alt="navbar brand" class="navbar-brand" height="40" />&nbsp;NISU
            </a>
            <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>
            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
    </div>
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                <li class="nav-item <?php if($page == 'dashboard.php') echo 'active'; ?>">
                    <a href="dashboard.php">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Management</h4>
                </li>

                <li class="nav-item <?php if($page == 'manage_alumni.php') echo 'active'; ?>">
                    <a href="manage_alumni.php">
                        <i class="fas fa-users"></i>
                        <p>Alumni Management</p>
                    </a>
                </li>

                <li class="nav-item <?php if($page == 'manage_colleges.php') echo 'active'; ?>">
                    <a href="manage_colleges.php">
                        <i class="fas fa-university"></i>
                        <p>Colleges & Programs</p>
                    </a>
                </li>

                <li class="nav-item <?php if($page == 'manage_batches.php') echo 'active'; ?>">
                    <a href="manage_batches.php">
                        <i class="fas fa-graduation-cap"></i>
                        <p>Batches</p>
                    </a>
                </li>

                <li class="nav-item <?php if($page == 'manage_announcements.php') echo 'active'; ?>">
                    <a href="manage_announcements.php">
                        <i class="fas fa-bullhorn"></i>
                        <p>Announcements</p>
                    </a>
                </li>

                <li class="nav-item <?php if($page == 'manage_events.php') echo 'active'; ?>">
                    <a href="manage_events.php">
                        <i class="fas fa-calendar"></i>
                        <p>Events</p>
                    </a>
                </li>

                <li class="nav-item <?php if($page == 'manage_photos.php') echo 'active'; ?>">
                    <a href="manage_photos.php">
                        <i class="fas fa-images"></i>
                        <p>Photo Gallery</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Reports</h4>
                </li>

                <li class="nav-item <?php if($page == 'reports.php') echo 'active'; ?>">
                    <a href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        <p>Reports</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">System</h4>
                </li>

                <li class="nav-item <?php if($page == 'profile.php') echo 'active'; ?>">
                    <a href="profile.php">
                        <i class="fas fa-user"></i>
                        <p>Profile</p>
                    </a>
                </li>

                <li class="nav-item <?php if($page == 'settings.php') echo 'active'; ?>">
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>
                        <p>Settings</p>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
