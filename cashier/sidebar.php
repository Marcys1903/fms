<!-- Sidebar -->
<aside class="bg-sidebar text-white w-72 flex-shrink-0 h-full overflow-y-auto pt-6 pb-10 px-4 hidden md:block border-r border-gray-800">
  <!-- Logo & Title -->
  <div class="mb-8 px-3">
    <div class="flex items-center gap-3 mb-3">
      <div class="w-9 h-9 rounded-lg bg-accent/10 flex items-center justify-center">
        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
      </div>
      <div>
        <h2 class="text-lg font-bold">Financial Management</h2>
        <p class="text-xs text-gray-400">Super Admin Panel</p>
      </div>
    </div>
    <div class="text-xs text-gray-400 bg-gray-800/50 rounded-lg px-3 py-2">
      <div class="flex items-center justify-between">
        <span>Last Login:</span>
        <span class="text-gray-300">Today, 09:42 AM</span>
      </div>
    </div>
  </div>
  
  <!-- Navigation -->
  <nav class="space-y-2 px-1">
    <!-- Dashboard Link (Always Visible) -->
    <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg bg-accent/15 text-accent hover:bg-accent/20 transition-all duration-200 group border border-accent/20">
      <div class="w-5 h-5">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
      </div>
      <span class="text-sm font-semibold">Dashboard</span>
      <span class="ml-auto text-xs bg-accent/20 px-2 py-0.5 rounded">Home</span>
    </a>
    
    <!-- Main Modules with Collapsible Submenus -->
    <div class="pt-2">
      <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 py-2">Financial Modules</p>
    </div>
    
    <!-- Module 1: Financial Planning & Budget Management -->
    <div x-data="{ open: false }" class="transition-all duration-200">
      <button @click="open = !open" 
              class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-800/70 transition-all duration-200 group"
              :class="open ? 'bg-gray-800/70' : ''">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center group-hover:bg-accent/20 transition-colors"
               :class="open ? 'bg-accent/20' : ''">
            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
          <div class="text-left">
            <span class="text-sm font-medium">Financial Planning</span>
            <p class="text-xs text-gray-400">Budget Management</p>
          </div>
        </div>
        <svg :class="open ? 'rotate-180' : ''" 
             class="w-4 h-4 text-gray-400 transition-transform duration-200" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>
      
      <div x-show="open" x-collapse class="ml-10 mt-1 mb-2 space-y-1 border-l border-gray-700/50 pl-4 py-2">
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-accent transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-accent opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Budget Planning</span>
          </div>
        </a>
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-accent transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-accent opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Approval Management</span>
          </div>
        </a>
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-accent transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-accent opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Fund Allocation</span>
          </div>
        </a>
      </div>
    </div>
    
    <!-- Module 2: Revenue & Payment Management -->
    <div x-data="{ open: false }" class="transition-all duration-200">
      <button @click="open = !open" 
              class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-800/70 transition-all duration-200 group"
              :class="open ? 'bg-gray-800/70' : ''">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center group-hover:bg-success/20 transition-colors"
               :class="open ? 'bg-success/20' : ''">
            <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="text-left">
            <span class="text-sm font-medium">Revenue & Payments</span>
            <p class="text-xs text-gray-400">Payment Management</p>
          </div>
        </div>
        <svg :class="open ? 'rotate-180' : ''" 
             class="w-4 h-4 text-gray-400 transition-transform duration-200" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>
      
      <div x-show="open" x-collapse class="ml-10 mt-1 mb-2 space-y-1 border-l border-gray-700/50 pl-4 py-2">
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-success transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-success opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Revenue Recording</span>
          </div>
        </a>
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-success transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-success opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Revenue Tracking</span>
          </div>
        </a>
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-success transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-success opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Accounts Receivable</span>
          </div>
        </a>
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-success transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-success opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Receivable Notification</span>
          </div>
        </a>
      </div>
    </div>
    
    <!-- Module 3: Expense, Procurement & Accounts Payable -->
    <div x-data="{ open: false }" class="transition-all duration-200">
      <button @click="open = !open" 
              class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-800/70 transition-all duration-200 group"
              :class="open ? 'bg-gray-800/70' : ''">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center group-hover:bg-warning/20 transition-colors"
               :class="open ? 'bg-warning/20' : ''">
            <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
          <div class="text-left">
            <span class="text-sm font-medium">Expense & Procurement</span>
            <p class="text-xs text-gray-400">Accounts Payable</p>
          </div>
        </div>
        <svg :class="open ? 'rotate-180' : ''" 
             class="w-4 h-4 text-gray-400 transition-transform duration-200" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>
      
      <div x-show="open" x-collapse class="ml-10 mt-1 mb-2 space-y-1 border-l border-gray-700/50 pl-4 py-2">
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-warning transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-warning opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Expense Logging</span>
          </div>
        </a>
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-warning transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-warning opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Disbursement Tracking</span>
          </div>
        </a>
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-warning transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-warning opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Payment Issuance</span>
          </div>
        </a>
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-warning transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-warning opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Accounts Payable</span>
          </div>
        </a>
      </div>
    </div>
    
    <!-- Module 4: Asset & Compliance Management -->
    <div x-data="{ open: false }" class="transition-all duration-200">
      <button @click="open = !open" 
              class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-800/70 transition-all duration-200 group"
              :class="open ? 'bg-gray-800/70' : ''">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-info/10 flex items-center justify-center group-hover:bg-info/20 transition-colors"
               :class="open ? 'bg-info/20' : ''">
            <svg class="w-4 h-4 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
          </div>
          <div class="text-left">
            <span class="text-sm font-medium">Asset & Compliance</span>
            <p class="text-xs text-gray-400">Management</p>
          </div>
        </div>
        <svg :class="open ? 'rotate-180' : ''" 
             class="w-4 h-4 text-gray-400 transition-transform duration-200" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>
      
      <div x-show="open" x-collapse class="ml-10 mt-1 mb-2 space-y-1 border-l border-gray-700/50 pl-4 py-2">
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-info transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-info opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Asset Inventory</span>
          </div>
        </a>
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-info transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-info opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Depreciation Monitoring</span>
          </div>
        </a>
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-info transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-info opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Compliance Reporting</span>
          </div>
        </a>
      </div>
    </div>
    
    <!-- Module 5: System Security & Audit Control -->
    <div x-data="{ open: false }" class="transition-all duration-200">
      <button @click="open = !open" 
              class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-800/70 transition-all duration-200 group"
              :class="open ? 'bg-gray-800/70' : ''">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-danger/10 flex items-center justify-center group-hover:bg-danger/20 transition-colors"
               :class="open ? 'bg-danger/20' : ''">
            <svg class="w-4 h-4 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
          </div>
          <div class="text-left">
            <span class="text-sm font-medium">Security & Audit</span>
            <p class="text-xs text-gray-400">System Control</p>
          </div>
        </div>
        <svg :class="open ? 'rotate-180' : ''" 
             class="w-4 h-4 text-gray-400 transition-transform duration-200" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>
      
      <div x-show="open" x-collapse class="ml-10 mt-1 mb-2 space-y-1 border-l border-gray-700/50 pl-4 py-2">
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-danger transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-danger opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>User Authentication</span>
          </div>
        </a>
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-danger transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-danger opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Access Management</span>
          </div>
        </a>
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-danger transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-danger opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Audit Trail Logging</span>
          </div>
        </a>
        <a href="#" class="block py-2 px-3 text-sm rounded hover:bg-gray-800/50 hover:text-danger transition-all duration-150 text-gray-300 hover:pl-4 group/sub">
          <div class="flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-danger opacity-0 group-hover/sub:opacity-100 transition-opacity"></div>
            <span>Security Compliance</span>
          </div>
        </a>
      </div>
    </div>
    
    <!-- System Management Section -->
    <div class="pt-4">
      <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 py-2">System Management</p>
    </div>
    
    <!-- Additional Navigation Links -->
    <a href="#" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-800/70 transition-all duration-200 group">
      <div class="w-8 h-8 rounded-lg bg-gray-700/50 flex items-center justify-center group-hover:bg-accent/20 transition-colors">
        <svg class="w-4 h-4 text-gray-400 group-hover:text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </div>
      <span class="text-sm font-medium text-gray-300 group-hover:text-white">Reports & Analytics</span>
      <span class="ml-auto text-xs bg-gray-700 px-2 py-0.5 rounded group-hover:bg-accent/20">New</span>
    </a>
    
    <a href="#" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-800/70 transition-all duration-200 group">
      <div class="w-8 h-8 rounded-lg bg-gray-700/50 flex items-center justify-center group-hover:bg-accent/20 transition-colors">
        <svg class="w-4 h-4 text-gray-400 group-hover:text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 3.75a6 6 0 00-9.5-4.197"/>
        </svg>
      </div>
      <span class="text-sm font-medium text-gray-300 group-hover:text-white">User Management</span>
    </a>
    
    <a href="#" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-800/70 transition-all duration-200 group">
      <div class="w-8 h-8 rounded-lg bg-gray-700/50 flex items-center justify-center group-hover:bg-accent/20 transition-colors">
        <svg class="w-4 h-4 text-gray-400 group-hover:text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
      </div>
      <span class="text-sm font-medium text-gray-300 group-hover:text-white">System Settings</span>
    </a>
  </nav>
  
  <!-- Sidebar Footer -->
  <div class="mt-8 pt-6 border-t border-gray-800 px-3">
    <div class="bg-gray-800/30 rounded-lg p-3 mb-3">
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
          <div class="w-2 h-2 bg-success rounded-full animate-pulse"></div>
          <span class="text-xs font-medium text-gray-300">System Status</span>
        </div>
        <span class="text-xs bg-success/20 text-success px-2 py-0.5 rounded">Operational</span>
      </div>
      
      <div class="text-xs text-gray-400 space-y-1">
        <div class="flex items-center justify-between">
          <span>Last Backup:</span>
          <span class="text-gray-300">Today, 02:00 AM</span>
        </div>
        <div class="flex items-center justify-between">
          <span>Storage:</span>
          <span class="text-gray-300">2.4TB / 4TB</span>
        </div>
      </div>
    </div>
    
    <button onclick="confirm('Initiate backup now?')" 
            class="w-full text-xs bg-accent hover:bg-blue-600 text-white py-2 px-3 rounded-lg transition-all duration-200 font-medium flex items-center justify-center gap-2">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
      </svg>
      Manual Backup
    </button>
  </div>
</aside>