<script setup>
import { Link } from '@inertiajs/vue3'
</script>
<template>

    <!-- Sidebar Start -->
    <aside class="left-sidebar">
        <!-- Sidebar scroll-->
        <div>
            <div class="brand-logo d-flex align-items-center justify-content-between">
                <a href="/" class="text-nowrap logo-img">
                    <img src="/public/assets/images/logos/larasafe.png" width="225px" alt="" />
                </a>
                <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
                    <i class="ti ti-x fs-8"></i>
                </div>
            </div>
            <!-- Sidebar navigation-->
            <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
                <ul id="sidebarnav">
                    <li class="nav-small-cap">
                        <iconify-icon icon="solar:menu-dots-linear" class="nav-small-cap-icon fs-4"></iconify-icon>
                    </li>
                    <li class="sidebar-item">
                        <Link class="sidebar-link primary-hover-bg" :href="`/`" aria-expanded="false">
                            <iconify-icon icon="solar:atom-line-duotone"></iconify-icon>
                            <span class="hide-menu">Dashboard</span>
                        </Link>
                    </li>
                    <!-- ---------------------------------- -->
                    <!-- Dashboard -->
                    <!-- ---------------------------------- -->
                    <li class="sidebar-item">
                        <Link class="sidebar-link primary-hover-bg justify-content-between"
                            :href="`/projects/manage-projects`"
                            aria-expanded="false">
                            <div class="d-flex align-items-center gap-6">
                                <span class="d-flex">
                                    <iconify-icon icon="solar:screencast-2-line-duotone" class=""></iconify-icon>
                                </span>
                                <span class="hide-menu">Manage Projects</span>
                            </div>
                        </Link>
                    </li>
                    <li class="sidebar-item">
                        <Link class="sidebar-link primary-hover-bg justify-content-between" target="_blank"
                            :href="`/backups/manage-backups`"
                            aria-expanded="false">
                            <div class="d-flex align-items-center gap-6">
                                <span class="d-flex">
                                    <iconify-icon icon="solar:chart-line-duotone" class=""></iconify-icon>
                                </span>
                                <span class="hide-menu">Manage Backups</span>
                            </div>
                        </Link>
                    </li>
                </ul>
            </nav>
            <!-- End Sidebar navigation -->
        </div>
        <!-- End Sidebar scroll-->
    </aside>
    <!--  Sidebar End -->
</template>