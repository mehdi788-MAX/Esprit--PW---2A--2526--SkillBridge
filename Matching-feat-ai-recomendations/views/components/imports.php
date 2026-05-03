<?php

function imports($path = "")
{
    return " 
    <!-- Bootstrap core JavaScript-->
    <script src='$path/assets/vendor/jquery/jquery.min.js'></script>
    <script src='$path/assets/vendor/bootstrap/js/bootstrap.bundle.min.js'></script>
    <script src='$path/assets/vendor/chart.js/Chart.min.js'></script>
    <script src='$path/assets/js/demo/chart-area-demo.js'></script>
    <script src='$path/assets/js/demo/chart-pie-demo.js'></script>

    <!-- Core plugin JavaScript-->
    <script src='$path/assets/vendor/jquery-easing/jquery.easing.min.js'></script>

    <!-- Custom scripts for all pages-->
    <script src='$path/assets/js/sb-admin-2.min.js'></script>

    <!-- Page level plugins -->
    <script src='$path/assets/vendor/chart.js/Chart.min.js'></script>


    ";
};
