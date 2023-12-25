<div class="header">
        <div class="navbar">
            <div class="logo">
                <a href="#"><img src=""></a>
            </div>
            <div class="menu">
                <ul>
                    <li><a href="dashboard.php">Home</a></li>
                    <li><a href="index_product.php">Product</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="supplier.php">Supplier</a></li>
                    <li><a href="purchase.php">Purchase</a></li>
                </ul>
            </div>
            <div class="user-profile">
                <img src="/images/user.png" alt="User Image">
                <h2><?= $first_name ?></h2>
            </div>
            <?php if ($_SERVER['PHP_SELF'] != "/register.php"): ?>
                <div class="register-btn"><a href="register.php">register</a></div>
            <?php endif; ?>
            <div class="icon-container">
                <a href="database/logout.php">
                    <i class="fa-solid fa-right-from-bracket fa-rotate-180 fa-lg" style="color: #ff3de5;"></i>
                </a>
            </div>
        </div>
    </div>
<script>
$(document).ready(function(){
  $(".menu ul li").click(function(){
    $(this).toggleClass("active");
  });
});
</script>