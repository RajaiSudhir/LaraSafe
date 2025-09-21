<script setup>
import Sidebar from "@/Components/Sidebar.vue";
import Header from "@/Components/Header.vue";
import Footer from "@/Components/Footer.vue";
</script>
<template>

<body>
  <!--  Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">


    <Tooltip />
    <Sidebar />
    <!--  Main wrapper -->
    <div class="body-wrapper">

      <div class="body-wrapper-inner">
        <div class="container-fluid">
            <Header />
            <slot />

            <Footer />
        </div>
      </div>
    </div>
  </div>


</body>



</template>