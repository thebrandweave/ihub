<?php
$current_page = basename($_SERVER['SCRIPT_NAME']);
$segments = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
$adminIndex = array_search('admin', $segments, true);
$depth = 0;
if ($adminIndex !== false) {
    $depth = max(0, count($segments) - ($adminIndex + 1) - 1);
}
$relativeRoot = str_repeat('../', $depth);
$logoutPath = $relativeRoot . '../auth/logout.php';
?>
<!-- Sidebar -->
<aside class="w-64 flex-shrink-0" aria-label="Sidebar">
    <div class="flex flex-col h-full bg-gray-800 text-white">
        <!-- Sidebar Header -->
        <div class="h-20 flex items-center justify-center bg-gray-900">
            <a href="<?= htmlspecialchars($relativeRoot . 'index.php', ENT_QUOTES, 'UTF-8') ?>" class="text-2xl font-bold">iHub Admin</a>
        </div>

        <!-- Sidebar Links -->
        <nav class="flex-1 px-4 py-4 space-y-2">
            <p class="px-4 py-2 text-xs text-gray-400 uppercase">Menu</p>
            <a href="<?= htmlspecialchars($relativeRoot . 'index.php', ENT_QUOTES, 'UTF-8') ?>" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md <?= ($current_page == 'index.php') ? 'bg-gray-700' : '' ?>">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="font-medium">Dashboard</span>
            </a>
            <a href="<?= htmlspecialchars($relativeRoot . 'admin/index.php', ENT_QUOTES, 'UTF-8') ?>" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md <?= ($current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/admin/admin') !== false) ? 'bg-gray-700' : '' ?>">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21a6 6 0 00-9-5.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-3-5.197M15 21a9 9 0 00-9-9"></path></svg>
                <span class="font-medium">Admins</span>
            </a>
            <a href="<?= htmlspecialchars($relativeRoot . 'products/index.php', ENT_QUOTES, 'UTF-8') ?>" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md <?= (strpos($_SERVER['REQUEST_URI'], '/admin/products') !== false) ? 'bg-gray-700' : '' ?>">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V7a2 2 0 00-2-2h-3V3a1 1 0 10-2 0v2h-4V3a1 1 0 10-2 0v2H4a2 2 0 00-2 2v6h4v5a1 1 0 102 0v-5h4v5a1 1 0 102 0v-5h4zM4 7h16v4H4z"></path></svg>
                <span class="font-medium">Products</span>
            </a>
            <a href="<?= htmlspecialchars($relativeRoot . 'users/index.php', ENT_QUOTES, 'UTF-8') ?>" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md <?= (strpos($_SERVER['REQUEST_URI'], '/admin/users') !== false) ? 'bg-gray-700' : '' ?>">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M15 21a6 6 0 00-9-5.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-3-5.197M15 21a9 9 0 00-9-9"></path></svg>
                <span class="font-medium">Users</span>
            </a>
            
        </nav>

        <!-- Sidebar Footer -->
        <div class="px-4 py-4 border-t border-gray-700">
             <a href="<?= htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8') ?>" class="flex items-center px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                <span class="font-medium">Logout</span>
            </a>
        </div>
    </div>
</aside>