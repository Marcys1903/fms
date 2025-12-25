<aside class="w-72 bg-gradient-to-b from-white to-gray-50 border-r border-gray-200/70 h-full overflow-hidden p-5 shadow-sm flex flex-col">
  <!-- Header Section with Elevated Design -->
  <div class="mb-8 pb-5 border-b border-gray-200/50">
    <div class="flex items-center gap-3 mb-2 px-1">
      <div class="w-8 h-8 bg-gradient-to-br from-accent/20 to-accent/10 rounded-lg flex items-center justify-center ring-1 ring-accent/20">
        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
      </div>
      <div>
        <span class="text-sm font-semibold text-gray-900 tracking-tight">FinanceHub</span>
        <p class="text-[11px] text-gray-500 font-medium tracking-wide mt-0.5">FINANCIAL MANAGEMENT SYSTEM</p>
      </div>
    </div>
  </div>

  <!-- Navigation Section - Scrollable with hidden scrollbar -->
  <nav class="flex-1 overflow-y-auto overflow-x-hidden space-y-1.5 scrollbar-hide px-1" 
       x-data="{ 
         openMenu: 'dashboard',
         toggleMenu(menu) {
           this.openMenu = this.openMenu === menu ? null : menu;
           // Scroll the clicked menu into view after a short delay for dropdown animation
           setTimeout(() => {
             const element = document.getElementById(`menu-${menu}`);
             if (element) {
               element.scrollIntoView({ 
                 behavior: 'smooth', 
                 block: 'center',
                 inline: 'nearest'
               });
             }
           }, 50);
         }
       }"
       x-init="$watch('openMenu', (value) => {
         if (value && value !== 'dashboard') {
           const element = document.getElementById(`menu-${value}`);
           if (element) {
             // Small delay to ensure dropdown is rendered
             setTimeout(() => {
               element.scrollIntoView({ 
                 behavior: 'smooth', 
                 block: 'center',
                 inline: 'nearest'
               });
             }, 100);
           }
         }
       })">
    
    <!-- Dashboard (Non-collapsible) - Elevated State -->
    <a href="#" class="flex items-center gap-3 px-3.5 py-3 rounded-lg bg-gradient-to-r from-accent/10 to-accent/5 text-accent font-semibold border-l-3 border-accent text-sm shadow-xs hover:shadow-sm transition-all duration-200 mb-2">
      <div class="w-5 h-5 bg-accent/20 rounded flex items-center justify-center">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
      </div>
      <span class="flex-1">Dashboard</span>
      <span class="text-[11px] bg-accent/20 text-accent px-2 py-1 rounded-full font-medium">ACTIVE</span>
    </a>

    <!-- Main Menu Items -->
    <div class="space-y-1.5">
      <!-- 1. Financial Planning -->
      <div id="menu-planning" class="group scroll-mt-4">
        <button @click="toggleMenu('planning')" 
                :class="{ 'bg-gray-100/80 text-gray-900 shadow-xs': openMenu === 'planning' }"
                class="w-full flex items-center justify-between gap-3 px-3.5 py-3 rounded-lg text-gray-700 hover:bg-gray-100/80 hover:text-gray-900 transition-all duration-200 text-sm group-hover:shadow-xs">
          <div class="flex items-center gap-3">
            <div class="w-5 h-5 text-gray-500 flex items-center justify-center group-hover:text-gray-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
            </div>
            <span class="font-medium text-sm">Financial Planning</span>
          </div>
          <svg class="w-3.5 h-3.5 text-gray-400 transition-all duration-200" 
               :class="{ 'rotate-90 text-gray-600': openMenu === 'planning' }" 
               fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
        
        <div x-show="openMenu === 'planning'" 
             x-collapse 
             x-transition:enter="transition-all ease-out duration-300"
             x-transition:enter-start="opacity-0 max-h-0"
             x-transition:enter-end="opacity-100 max-h-96"
             x-transition:leave="transition-all ease-in duration-200"
             x-transition:leave-start="opacity-100 max-h-96"
             x-transition:leave-end="opacity-0 max-h-0"
             class="ml-9 mt-1.5 space-y-1 border-l border-gray-200/50 pl-3">
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate flex-1">Budget Planning</span>
            <span class="text-[11px] bg-blue-100 text-blue-700 px-2 py-1 rounded-full ml-2 flex-shrink-0 font-medium">DRAFT</span>
          </a>
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Approval Management</span>
          </a>
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Fund Allocation</span>
          </a>
        </div>
      </div>

      <!-- 2. Revenue & Payments -->
      <div id="menu-revenue" class="group scroll-mt-4">
        <button @click="toggleMenu('revenue')" 
                :class="{ 'bg-gray-100/80 text-gray-900 shadow-xs': openMenu === 'revenue' }"
                class="w-full flex items-center justify-between gap-3 px-3.5 py-3 rounded-lg text-gray-700 hover:bg-gray-100/80 hover:text-gray-900 transition-all duration-200 text-sm group-hover:shadow-xs">
          <div class="flex items-center gap-3">
            <div class="w-5 h-5 text-gray-500 flex items-center justify-center group-hover:text-gray-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <span class="font-medium text-sm">Revenue & Payments</span>
          </div>
          <svg class="w-3.5 h-3.5 text-gray-400 transition-all duration-200" 
               :class="{ 'rotate-90 text-gray-600': openMenu === 'revenue' }" 
               fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
        
        <div x-show="openMenu === 'revenue'" 
             x-collapse 
             x-transition:enter="transition-all ease-out duration-300"
             x-transition:enter-start="opacity-0 max-h-0"
             x-transition:enter-end="opacity-100 max-h-96"
             x-transition:leave="transition-all ease-in duration-200"
             x-transition:leave-start="opacity-100 max-h-96"
             x-transition:leave-end="opacity-0 max-h-0"
             class="ml-9 mt-1.5 space-y-1 border-l border-gray-200/50 pl-3">
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Revenue Recording</span>
          </a>
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Revenue Tracking</span>
            <span class="text-[11px] bg-green-100 text-green-700 px-2 py-1 rounded-full ml-2 flex-shrink-0 font-medium">LIVE</span>
          </a>
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Accounts Receivable</span>
          </a>
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Receivable Notification</span>
          </a>
        </div>
      </div>

      <!-- 3. Expense & AP -->
      <div id="menu-expense" class="group scroll-mt-4">
        <button @click="toggleMenu('expense')" 
                :class="{ 'bg-gray-100/80 text-gray-900 shadow-xs': openMenu === 'expense' }"
                class="w-full flex items-center justify-between gap-3 px-3.5 py-3 rounded-lg text-gray-700 hover:bg-gray-100/80 hover:text-gray-900 transition-all duration-200 text-sm group-hover:shadow-xs">
          <div class="flex items-center gap-3">
            <div class="w-5 h-5 text-gray-500 flex items-center justify-center group-hover:text-gray-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
            </div>
            <span class="font-medium text-sm">Expense & AP</span>
          </div>
          <svg class="w-3.5 h-3.5 text-gray-400 transition-all duration-200" 
               :class="{ 'rotate-90 text-gray-600': openMenu === 'expense' }" 
               fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
        
        <div x-show="openMenu === 'expense'" 
             x-collapse 
             x-transition:enter="transition-all ease-out duration-300"
             x-transition:enter-start="opacity-0 max-h-0"
             x-transition:enter-end="opacity-100 max-h-96"
             x-transition:leave="transition-all ease-in duration-200"
             x-transition:leave-start="opacity-100 max-h-96"
             x-transition:leave-end="opacity-0 max-h-0"
             class="ml-9 mt-1.5 space-y-1 border-l border-gray-200/50 pl-3">
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Expense Logging</span>
          </a>
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Disbursement Tracking</span>
          </a>
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Payment Issuance</span>
          </a>
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Accounts Payable</span>
          </a>
        </div>
      </div>

      <!-- 4. Asset & Compliance -->
      <div id="menu-asset" class="group scroll-mt-4">
        <button @click="toggleMenu('asset')" 
                :class="{ 'bg-gray-100/80 text-gray-900 shadow-xs': openMenu === 'asset' }"
                class="w-full flex items-center justify-between gap-3 px-3.5 py-3 rounded-lg text-gray-700 hover:bg-gray-100/80 hover:text-gray-900 transition-all duration-200 text-sm group-hover:shadow-xs">
          <div class="flex items-center gap-3">
            <div class="w-5 h-5 text-gray-500 flex items-center justify-center group-hover:text-gray-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
            </div>
            <span class="font-medium text-sm">Asset & Compliance</span>
          </div>
          <svg class="w-3.5 h-3.5 text-gray-400 transition-all duration-200" 
               :class="{ 'rotate-90 text-gray-600': openMenu === 'asset' }" 
               fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
        
        <div x-show="openMenu === 'asset'" 
             x-collapse 
             x-transition:enter="transition-all ease-out duration-300"
             x-transition:enter-start="opacity-0 max-h-0"
             x-transition:enter-end="opacity-100 max-h-96"
             x-transition:leave="transition-all ease-in duration-200"
             x-transition:leave-start="opacity-100 max-h-96"
             x-transition:leave-end="opacity-0 max-h-0"
             class="ml-9 mt-1.5 space-y-1 border-l border-gray-200/50 pl-3">
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Asset Inventory</span>
          </a>
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Depreciation Monitoring</span>
          </a>
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Compliance Reporting</span>
            <span class="text-[11px] bg-purple-100 text-purple-700 px-2 py-1 rounded-full ml-2 flex-shrink-0 font-medium">AUDIT</span>
          </a>
        </div>
      </div>

      <!-- 5. Security & Audit -->
      <div id="menu-security" class="group scroll-mt-4">
        <button @click="toggleMenu('security')" 
                :class="{ 'bg-gray-100/80 text-gray-900 shadow-xs': openMenu === 'security' }"
                class="w-full flex items-center justify-between gap-3 px-3.5 py-3 rounded-lg text-gray-700 hover:bg-gray-100/80 hover:text-gray-900 transition-all duration-200 text-sm group-hover:shadow-xs">
          <div class="flex items-center gap-3">
            <div class="w-5 h-5 text-gray-500 flex items-center justify-center group-hover:text-gray-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
              </svg>
            </div>
            <span class="font-medium text-sm">Security & Audit</span>
          </div>
          <svg class="w-3.5 h-3.5 text-gray-400 transition-all duration-200" 
               :class="{ 'rotate-90 text-gray-600': openMenu === 'security' }" 
               fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
        
        <div x-show="openMenu === 'security'" 
             x-collapse 
             x-transition:enter="transition-all ease-out duration-300"
             x-transition:enter-start="opacity-0 max-h-0"
             x-transition:enter-end="opacity-100 max-h-96"
             x-transition:leave="transition-all ease-in duration-200"
             x-transition:leave-start="opacity-100 max-h-96"
             x-transition:leave-end="opacity-0 max-h-0"
             class="ml-9 mt-1.5 space-y-1 border-l border-gray-200/50 pl-3">
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">User Authentication</span>
          </a>
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Access Management</span>
          </a>
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Audit Trail Logging</span>
          </a>
          <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-gray-600 hover:bg-gray-100/70 hover:text-gray-900 rounded-md transition-all duration-150 hover:translate-x-0.5">
            <div class="w-2 h-2 bg-gray-400 rounded-full flex-shrink-0"></div>
            <span class="truncate">Security Compliance</span>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Enhanced Divider - Fixed position -->
  <div class="my-3 relative flex-shrink-0">
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
      <div class="w-full border-t border-gray-300/30"></div>
    </div>
    <div class="relative flex justify-center">
      <span class="px-3 bg-white text-xs text-gray-500 font-medium">SYSTEM</span>
    </div>
  </div>

  <!-- Fixed Footer Section - Always visible -->
  <div class="mt-auto pt-3 space-y-4 flex-shrink-0 px-1">
    <!-- Enhanced User Section -->
    <div>
      <div class="flex items-center gap-3 mb-3 p-3 rounded-lg bg-gray-100/50 hover:bg-gray-100 transition-colors duration-200 cursor-pointer">
        <div class="relative">
          <div class="w-9 h-9 bg-gradient-to-br from-gray-300 to-gray-200 rounded-full flex items-center justify-center ring-2 ring-white shadow-xs">
            <span class="text-sm font-semibold text-gray-700">JD</span>
          </div>
          <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-green-500 rounded-full border-2 border-white"></div>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 leading-tight truncate">John Doe</p>
          <p class="text-xs text-gray-500 leading-tight font-medium mt-0.5">Finance Manager</p>
        </div>
        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
      </div>
      
      <a href="auth/logout.php" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-danger hover:bg-danger/10 rounded-lg transition-all duration-150 font-medium hover:shadow-xs group">
        <div class="w-5 h-5 bg-danger/10 rounded flex items-center justify-center group-hover:bg-danger/20 transition-colors">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
          </svg>
        </div>
        <span>Sign Out</span>
      </a>
    </div>

    <!-- Status Bar -->
    <div class="pt-4 border-t border-gray-200/50">
      <div class="flex items-center justify-between px-2">
        <div class="flex items-center gap-2">
          <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
          <span class="text-xs text-gray-600 font-medium">System Online</span>
        </div>
        <span class="text-xs text-gray-500 font-mono">v2.4.1</span>
      </div>
    </div>
  </div>
</aside>

<!-- Alpine.js CDN -->
<script src="//unpkg.com/alpinejs" defer></script>

<style>
  /* Hide scrollbar for Chrome, Safari and Opera */
  .scrollbar-hide::-webkit-scrollbar {
    display: none;
  }
  
  /* Hide scrollbar for IE, Edge and Firefox */
  .scrollbar-hide {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
  }
  
  /* Add smooth scrolling for better UX */
  .scrollbar-hide {
    scroll-behavior: smooth;
  }
  
  /* Focus styles for better accessibility */
  .group:focus-within {
    position: relative;
    z-index: 10;
  }
  
  /* Smooth dropdown animations */
  .max-h-0 {
    max-height: 0;
  }
  
  .max-h-96 {
    max-height: 24rem;
  }
</style>