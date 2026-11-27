window.adminUserEditor = (state) => ({
            form: {
                name: state.name || '',
                username: state.username || '',
                isActive: Boolean(state.isActive),
                roles: Array.isArray(state.roles) ? state.roles : [],
                gudangIds: Array.isArray(state.gudangIds) ? state.gudangIds : [],
            },
            roleOptions: Array.isArray(state.roleOptions) ? state.roleOptions : [],
            warehouseOptions: Array.isArray(state.warehouseOptions) ? state.warehouseOptions : [],
            password: '',
            passwordConfirmation: '',
            showPassword: false,
            showPasswordConfirmation: false,
            get initials() {
                return this.form.name.trim().split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0])
                    .join('').toUpperCase() || 'U';
            },
            get selectedRoleOptions() {
                return this.roleOptions.filter((role) => this.form.roles.includes(role.name));
            },
            get selectedWarehouseOptions() {
                return this.warehouseOptions.filter((warehouse) => this.form.gudangIds.includes(String(warehouse
                    .id)));
            },
            get effectivePermissions() {
                return [...new Set(this.selectedRoleOptions.flatMap((role) => role.permissions || []))].sort();
            },
            get visiblePermissions() {
                return this.effectivePermissions.slice(0, 8);
            },
        });
