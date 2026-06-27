@extends('approval-mapping::layouts.app')

@section('title', 'Approval Mapping Versions')

@section('content')
<div class="am-page" x-data="approvalMappingApp()" x-init="init()">
    <div class="am-title-pill">Approval Mapping Versions List</div>

    <div class="am-toolbar">
        <button type="button" class="am-btn am-btn-icon" @click="showSearch = !showSearch" title="Search">&#128269;</button>
        <button type="button" class="am-btn am-btn-primary" @click="openCreateModal()">+ Add new</button>
        <select class="am-select" x-show="appFeatures.company" x-model="filters.company_id" @change="onFilterCompanyChange()">
            <option value="">Filter by company</option>
            <template x-for="item in lookups.companies" :key="item.value">
                <option :value="item.value" x-text="item.text"></option>
            </template>
        </select>
        <select class="am-select" x-show="appFeatures.business_unit" x-model="filters.business_unit_id" @change="loadVersions(1)">
            <option value="">Filter by business unit</option>
            <template x-for="item in lookups.businessUnits" :key="item.value">
                <option :value="item.value" x-text="item.text"></option>
            </template>
        </select>
        <select class="am-select" x-show="appFeatures.module" x-model="filters.module_reference" @change="loadVersions(1)">
            <option value="">Filter by module</option>
            <template x-for="item in lookups.modules" :key="item.value">
                <option :value="item.value" x-text="item.text"></option>
            </template>
        </select>
    </div>

    <div class="am-search-row" x-show="showSearch" x-cloak>
        <input type="text" class="am-input" placeholder="Search version, notes..." x-model="filters.search" @keydown.enter="loadVersions(1)">
        <button type="button" class="am-btn" @click="loadVersions(1)">Search</button>
    </div>

    <div class="am-alert am-alert-error" x-show="errorMessage" x-text="errorMessage" x-cloak></div>
    <div class="am-alert am-alert-success" x-show="successMessage" x-text="successMessage" x-cloak></div>

    <div class="am-card">
        <div class="am-table-wrap">
            <table class="am-table">
                <thead>
                    <tr>
                        <th>Version</th>
                        <th x-show="appFeatures.company">Company</th>
                        <th x-show="appFeatures.business_unit">Business Unit</th>
                        <th x-show="appFeatures.module">Module</th>
                        <th>Effective from</th>
                        <th>Effective to</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <tr><td :colspan="tableColspan()">Loading...</td></tr>
                    </template>
                    <template x-if="!loading && versions.length === 0">
                        <tr><td :colspan="tableColspan()">No approval mapping versions found.</td></tr>
                    </template>
                    <template x-for="version in versions" :key="version.id">
                        <tr>
                            <td x-text="version.version"></td>
                            <td x-show="appFeatures.company" x-text="version.company?.name || '-'"></td>
                            <td x-show="appFeatures.business_unit" x-text="version.business_unit?.name || '-'"></td>
                            <td x-show="appFeatures.module" x-text="moduleLabel(version)"></td>
                            <td x-text="formatDate(version.effective_from)"></td>
                            <td x-text="version.effective_to ? formatDate(version.effective_to) : '-'"></td>
                            <td>
                                <span class="am-badge" :class="version.is_active ? 'am-badge-active' : 'am-badge-inactive'" x-text="version.is_active ? 'Active' : 'Inactive'"></span>
                            </td>
                            <td x-text="version.notes || '-'"></td>
                            <td>
                                <div class="am-actions">
                                    <button type="button" class="am-btn am-btn-primary" @click="openDetailsModal(version)">&#128065; View details</button>
                                    <button type="button" class="am-btn am-btn-icon" title="Sync to module" x-show="version.supports_sync" @click="syncToModule(version)">&#8635;</button>
                                    <button type="button" class="am-btn am-btn-icon" title="Edit" @click="openEditModal(version)">&#9998;</button>
                                    <button type="button" class="am-btn am-btn-icon am-btn-danger" title="Delete" @click="deleteVersion(version)">&#128465;</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="am-footer">
            <div>
                <select class="am-select" x-model.number="pagination.per_page" @change="loadVersions(1)">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <span class="am-muted" style="margin-left:8px;" x-text="paginationSummary()"></span>
            </div>
            <div class="am-pagination">
                <button type="button" @click="loadVersions(pagination.current_page - 1)" :disabled="pagination.current_page <= 1">&lt;</button>
                <template x-for="page in pageNumbers()" :key="page">
                    <button type="button" :class="page === pagination.current_page ? 'active' : ''" @click="loadVersions(page)" x-text="page"></button>
                </template>
                <button type="button" @click="loadVersions(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page">&gt;</button>
            </div>
        </div>
    </div>

    <!-- Version form modal -->
    <div class="am-modal-backdrop" x-show="versionForm.open" x-cloak @click.self="versionForm.open = false">
        <div class="am-modal" style="width:min(760px,100%);">
            <div class="am-modal-header">
                <h3 x-text="versionForm.id ? 'Edit Approval Mapping Version' : 'Add Approval Mapping Version'"></h3>
                <button type="button" class="am-btn am-btn-icon" @click="versionForm.open = false">&times;</button>
            </div>
            <div class="am-modal-body">
                <div class="am-form-grid">
                    <div class="am-form-group">
                        <label>Version</label>
                        <input type="text" class="am-input" x-model="versionForm.version">
                    </div>
                    <div class="am-form-group" x-show="appFeatures.company">
                        <label>Company</label>
                        <select class="am-select" x-model="versionForm.company_id" @change="onVersionCompanyChange()">
                            <option value="">Select company</option>
                            <template x-for="item in lookups.companies" :key="item.value">
                                <option :value="item.value" x-text="item.text"></option>
                            </template>
                        </select>
                    </div>
                    <div class="am-form-group" x-show="appFeatures.business_unit">
                        <label>Business Unit</label>
                        <select class="am-select" x-model="versionForm.business_unit_id">
                            <option value="">Select business unit</option>
                            <template x-for="item in lookups.businessUnits" :key="item.value">
                                <option :value="item.value" x-text="item.text"></option>
                            </template>
                        </select>
                    </div>
                    <div class="am-form-group" x-show="appFeatures.module">
                        <label>Module</label>
                        <select class="am-select" x-model="versionForm.module_reference">
                            <option value="">Select module</option>
                            <template x-for="item in lookups.modules" :key="item.value">
                                <option :value="item.value" x-text="item.text"></option>
                            </template>
                        </select>
                    </div>
                    <div class="am-form-group">
                        <label>Effective from</label>
                        <input type="date" class="am-input" x-model="versionForm.effective_from">
                    </div>
                    <div class="am-form-group">
                        <label>Effective to</label>
                        <input type="date" class="am-input" x-model="versionForm.effective_to">
                    </div>
                    <div class="am-form-group">
                        <label>Status</label>
                        <select class="am-select" x-model="versionForm.is_active">
                            <option :value="true">Active</option>
                            <option :value="false">Inactive</option>
                        </select>
                    </div>
                    <div class="am-form-group" style="grid-column:1/-1;">
                        <label>Notes</label>
                        <textarea class="am-input" rows="3" x-model="versionForm.notes" style="width:100%;"></textarea>
                    </div>
                </div>
            </div>
            <div class="am-modal-footer" style="justify-content:flex-end;">
                <button type="button" class="am-btn" @click="versionForm.open = false">Cancel</button>
                <button type="button" class="am-btn am-btn-primary" @click="saveVersionForm()">Save</button>
            </div>
        </div>
    </div>

    <!-- Version details modal -->
    <div class="am-modal-backdrop" x-show="details.open" x-cloak @click.self="details.open = false">
        <div class="am-modal">
            <div class="am-modal-header">
                <h3 x-text="'Version details - ' + (details.version?.version || '')"></h3>
                <button type="button" class="am-btn am-btn-icon" @click="details.open = false">&times;</button>
            </div>
            <div class="am-modal-body">
                <div class="am-meta-grid">
                    <div><strong>Version</strong><span x-text="details.version?.version || '-'"></span></div>
                    <div><strong>Effective from</strong><span x-text="formatDate(details.version?.effective_from)"></span></div>
                    <div><strong>Effective to</strong><span x-text="details.version?.effective_to ? formatDate(details.version.effective_to) : '-'"></span></div>
                    <div x-show="appFeatures.company"><strong>Company</strong><span x-text="details.version?.company?.name || '-'"></span></div>
                    <div x-show="appFeatures.business_unit"><strong>Business Unit</strong><span x-text="details.version?.business_unit?.name || '-'"></span></div>
                    <div x-show="appFeatures.module"><strong>Module</strong><span x-text="moduleLabel(details.version) || '-'"></span></div>
                    <div><strong>Status</strong><span class="am-badge am-badge-active" x-show="details.version?.is_active">Active</span><span class="am-badge am-badge-inactive" x-show="!details.version?.is_active">Inactive</span></div>
                </div>

                <div class="am-search-row" x-show="appFeatures.department">
                    <span>&#128269;</span>
                    <input type="text" class="am-input" placeholder="Search department..." x-model="details.departmentSearch">
                </div>

                <div class="am-table-wrap">
                    <table class="am-table">
                        <thead>
                            <tr>
                                <th x-show="appFeatures.department">Department</th>
                                <th x-show="appFeatures.branch">Branch</th>
                                <th>Type</th>
                                <template x-for="level in details.level_columns" :key="'head-' + level">
                                    <th>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <span x-text="'Level ' + level"></span>
                                        </div>
                                    </th>
                                </template>
                                <th>
                                    <button type="button" class="am-btn am-btn-icon am-btn-primary" @click="addLevelColumn()">+</button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(group, groupIndex) in filteredDetailGroups()" :key="'group-' + groupIndex + '-' + group.department">
                                <template x-for="(subRow, subRowIndex) in group.subRows" :key="'subrow-' + groupIndex + '-' + subRowIndex">
                                    <tr>
                                        <td x-show="appFeatures.department">
                                            <template x-if="subRowIndex === 0 && group._new">
                                                <input type="text" class="am-input" x-model="group.department" placeholder="Department name">
                                            </template>
                                            <template x-if="subRowIndex === 0 && !group._new">
                                                <span x-text="group.department"></span>
                                            </template>
                                        </td>
                                        <td x-show="appFeatures.branch">
                                            <select class="am-select" x-model="subRow.branch_id">
                                                <option value="">Select branch</option>
                                                <template x-for="branch in detailBranchOptions()" :key="branch.value">
                                                    <option :value="branch.value" x-text="branch.text"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="am-select" x-model="subRow.type">
                                                <option value="direct">Direct</option>
                                                <option value="agency">Agency</option>
                                            </select>
                                        </td>
                                        <template x-for="level in details.level_columns" :key="'cell-' + groupIndex + '-' + subRowIndex + '-' + level">
                                            <td>
                                                <div class="am-level-cell">
                                                    <template x-for="(groupId, groupIdIndex) in subRow.cells[level]" :key="groupIdIndex">
                                                        <div style="display:flex;align-items:center;gap:6px;">
                                                            <select class="am-select" x-model="subRow.cells[level][groupIdIndex]">
                                                                <option value="">Select approver group</option>
                                                                <template x-for="groupOption in lookups.userAssignGroups" :key="groupOption.value">
                                                                    <option :value="groupOption.value" x-text="groupOption.text"></option>
                                                                </template>
                                                            </select>
                                                            <button type="button" class="am-btn am-btn-icon am-btn-danger" style="width:28px;height:28px;" @click="removeApprover(subRow, level, groupIdIndex)">-</button>
                                                        </div>
                                                    </template>
                                                    <button type="button" class="am-btn am-btn-icon" style="width:28px;height:28px;" @click="addApprover(subRow, level)">+</button>
                                                </div>
                                            </td>
                                        </template>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:6px;">
                                                <button type="button" class="am-btn am-btn-icon am-btn-danger" @click="removeSubRow(groupIndex, subRowIndex)">&#128465;</button>
                                                <button type="button" class="am-btn am-btn-icon am-btn-success" x-show="subRowIndex === group.subRows.length - 1" @click="addSubRow(group)">+</button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="am-modal-footer">
                <div style="display:flex;gap:8px;">
                    <button type="button" class="am-btn am-btn-muted" @click="details.open = false">Close</button>
                    <button type="button" class="am-btn am-btn-info" @click="openSaveAsNewModal()">&#128190; Save as new version</button>
                </div>
                <button type="button" class="am-btn am-btn-primary" @click="saveDetails()">Update</button>
            </div>
        </div>
    </div>

    <!-- Save as new modal -->
    <div class="am-modal-backdrop" x-show="saveAsNew.open" x-cloak @click.self="saveAsNew.open = false">
        <div class="am-modal" style="width:min(560px,100%);">
            <div class="am-modal-header">
                <h3>Save as new version</h3>
                <button type="button" class="am-btn am-btn-icon" @click="saveAsNew.open = false">&times;</button>
            </div>
            <div class="am-modal-body">
                <div class="am-form-grid">
                    <div class="am-form-group">
                        <label>New version</label>
                        <input type="text" class="am-input" x-model="saveAsNew.new_version">
                    </div>
                    <div class="am-form-group">
                        <label>Effective from</label>
                        <input type="date" class="am-input" x-model="saveAsNew.effective_from">
                    </div>
                    <div class="am-form-group" style="grid-column:1/-1;">
                        <label>Notes</label>
                        <textarea class="am-input" rows="3" x-model="saveAsNew.notes" style="width:100%;"></textarea>
                    </div>
                </div>
            </div>
            <div class="am-modal-footer" style="justify-content:flex-end;">
                <button type="button" class="am-btn" @click="saveAsNew.open = false">Cancel</button>
                <button type="button" class="am-btn am-btn-primary" @click="saveAsNewVersion()">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function approvalMappingApp() {
    return {
        apiBase: @json($apiBase),
        csrfToken: @json($csrfToken),
        appFeatures: @json($features),
        loading: false,
        showSearch: false,
        errorMessage: '',
        successMessage: '',
        versions: [],
        pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 },
        filters: { search: '', company_id: '', business_unit_id: '', module_reference: '' },
        lookups: { companies: [], businessUnits: [], modules: [], branches: [], departments: [], userAssignGroups: [] },
        detailLookups: { branches: [], departments: [] },
        versionForm: { open: false, id: null, version: '', company_id: '', business_unit_id: '', module_reference: '', effective_from: '', effective_to: '', is_active: true, notes: '' },
        details: { open: false, version: null, level_columns: [1], groups: [], departmentSearch: '' },
        saveAsNew: { open: false, new_version: '', effective_from: '', notes: '' },

        async init() {
            await Promise.all([
                this.loadLookups(),
                this.loadVersions(1),
            ]);
        },

        async request(path, options = {}) {
            const headers = {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                ...(options.headers || {}),
            };

            if (options.body && !(options.body instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(options.body);
            }

            const response = await fetch(`${this.apiBase}/${path}`.replace(/\/+/g, '/').replace(':/', '://'), {
                ...options,
                headers,
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }

            return data;
        },

        async loadLookups() {
            const promises = [
                this.request('lookup/user-assign-groups').then(v => { this.lookups.userAssignGroups = v; }),
            ];

            if (this.appFeatures.company) {
                promises.push(this.request('lookup/companies').then(v => { this.lookups.companies = v; }));
            }

            if (this.appFeatures.business_unit) {
                promises.push(this.request('lookup/business-units').then(v => { this.lookups.businessUnits = v; }));
            }

            if (this.appFeatures.module) {
                promises.push(this.request('lookup/modules').then(v => { this.lookups.modules = v; }));
            }

            if (this.appFeatures.branch) {
                promises.push(this.request('lookup/branches').then(v => { this.lookups.branches = v; }));
            }

            await Promise.all(promises);
        },

        async loadBusinessUnits(companyId, target = 'lookups') {
            if (!this.appFeatures.business_unit) return [];
            const params = companyId ? `?company_id=${companyId}` : '';
            const businessUnits = await this.request(`lookup/business-units${params}`);
            if (target === 'lookups') {
                this.lookups.businessUnits = businessUnits;
            }
            return businessUnits;
        },

        async loadBranches(companyId, businessUnitId, target = 'lookups') {
            if (!this.appFeatures.branch) return [];
            const params = new URLSearchParams();
            if (companyId) params.set('company_id', companyId);
            if (businessUnitId) params.set('business_unit_id', businessUnitId);
            const query = params.toString() ? `?${params.toString()}` : '';
            const branches = await this.request(`lookup/branches${query}`);
            if (target === 'lookups') {
                this.lookups.branches = branches;
            } else if (target === 'detailLookups') {
                this.detailLookups.branches = branches;
            }
            return branches;
        },

        async loadDepartments(companyId, businessUnitId, branchId = null, target = 'lookups') {
            if (!this.appFeatures.department) return [];
            const params = new URLSearchParams();
            if (companyId) params.set('company_id', companyId);
            if (businessUnitId) params.set('business_unit_id', businessUnitId);
            if (branchId) params.set('branch_id', branchId);
            const query = params.toString() ? `?${params.toString()}` : '';
            const departments = await this.request(`lookup/departments${query}`);
            if (target === 'detailLookups') {
                this.detailLookups.departments = departments;
            }
            return departments;
        },

        onFilterCompanyChange() {
            this.filters.business_unit_id = '';
            if (this.appFeatures.business_unit) {
                this.loadBusinessUnits(this.filters.company_id || '');
            }
            this.loadVersions(1);
        },

        onVersionCompanyChange() {
            this.versionForm.business_unit_id = '';
            if (this.appFeatures.business_unit) {
                this.loadBusinessUnits(this.versionForm.company_id || '');
            }
        },

        async loadVersions(page = 1) {
            this.loading = true;
            this.errorMessage = '';
            try {
                const params = new URLSearchParams({
                    page: String(page),
                    per_page: String(this.pagination.per_page),
                    search: this.filters.search || '',
                });

                if (this.appFeatures.company && this.filters.company_id) {
                    params.set('company_id', this.filters.company_id);
                }
                if (this.appFeatures.business_unit && this.filters.business_unit_id) {
                    params.set('business_unit_id', this.filters.business_unit_id);
                }
                if (this.appFeatures.module && this.filters.module_reference) {
                    params.set('module_reference', this.filters.module_reference);
                }

                const data = await this.request(`versions?${params.toString()}`);
                this.versions = data.data || [];
                this.pagination = {
                    current_page: data.current_page || 1,
                    last_page: data.last_page || 1,
                    per_page: data.per_page || 10,
                    total: data.total || 0,
                    from: data.from || 0,
                    to: data.to || 0,
                };
            } catch (error) {
                this.errorMessage = error.message;
            } finally {
                this.loading = false;
            }
        },

        openCreateModal() {
            this.versionForm = {
                open: true,
                id: null,
                version: '',
                company_id: '',
                business_unit_id: '',
                module_reference: '',
                effective_from: new Date().toISOString().slice(0, 10),
                effective_to: '',
                is_active: true,
                notes: '',
            };
        },

        async openEditModal(version) {
            this.versionForm = {
                open: true,
                id: version.id,
                version: version.version,
                company_id: version.company_id || '',
                business_unit_id: version.business_unit_id || '',
                module_reference: version.module_reference || '',
                effective_from: version.effective_from || '',
                effective_to: version.effective_to || '',
                is_active: !!version.is_active,
                notes: version.notes || '',
            };
            if (version.company_id && this.appFeatures.business_unit) {
                await this.loadBusinessUnits(version.company_id);
            }
        },

        async saveVersionForm() {
            this.errorMessage = '';
            try {
                const payload = {
                    version: this.versionForm.version,
                    company_id: this.appFeatures.company ? (this.versionForm.company_id || null) : null,
                    business_unit_id: this.appFeatures.business_unit ? (this.versionForm.business_unit_id || null) : null,
                    module_reference: this.appFeatures.module ? (this.versionForm.module_reference || null) : null,
                    effective_from: this.versionForm.effective_from,
                    effective_to: this.versionForm.effective_to || null,
                    is_active: this.versionForm.is_active,
                    notes: this.versionForm.notes || null,
                };

                if (this.versionForm.id) {
                    await this.request(`versions/${this.versionForm.id}`, { method: 'PUT', body: payload });
                    this.successMessage = 'Version updated successfully.';
                } else {
                    await this.request('versions', { method: 'POST', body: payload });
                    this.successMessage = 'Version created successfully.';
                }

                this.versionForm.open = false;
                await this.loadVersions(this.pagination.current_page);
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        async deleteVersion(version) {
            if (!confirm(`Delete version ${version.version}?`)) {
                return;
            }

            try {
                await this.request(`versions/${version.id}`, { method: 'DELETE' });
                this.successMessage = 'Version deleted successfully.';
                await this.loadVersions(this.pagination.current_page);
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        async openDetailsModal(version) {
            this.errorMessage = '';
            try {
                const data = await this.request(`versions/${version.id}/details`);
                this.details.version = data.version;
                this.details.level_columns = data.level_columns?.length ? data.level_columns : [1];
                const groupMap = {};
                (data.rows || []).forEach(row => {
                    const department = row.department || 'Default';
                    if (!groupMap[department]) {
                        groupMap[department] = {
                            department,
                            _new: false,
                            subRows: [],
                        };
                    }

                    groupMap[department].subRows.push({
                        branch_id: row.branch_id ? String(row.branch_id) : '',
                        type: row.type || 'direct',
                        cells: this.normalizeCells(row.cells || {}, this.details.level_columns),
                    });
                });
                this.details.groups = Object.values(groupMap);
                if (this.details.groups.length === 0) {
                    this.details.groups = [{
                        department: this.appFeatures.department ? '' : 'Default',
                        _new: this.appFeatures.department,
                        subRows: [this.createEmptySubRow()],
                    }];
                }
                this.details.departmentSearch = '';

                const loaders = [];
                if (this.appFeatures.branch) {
                    const companyId = data.version?.company_id || '';
                    const businessUnitId = data.version?.business_unit_id || '';
                    loaders.push(this.loadBranches(companyId, businessUnitId, 'detailLookups'));
                }
                if (this.appFeatures.department) {
                    const companyId = data.version?.company_id || '';
                    const businessUnitId = data.version?.business_unit_id || '';
                    loaders.push(this.loadDepartments(companyId, businessUnitId, null, 'detailLookups'));
                }

                await Promise.all(loaders);
                this.details.open = true;
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        detailBranchOptions() {
            return this.detailLookups.branches.length ? this.detailLookups.branches : this.lookups.branches;
        },

        normalizeCells(cells, levels) {
            const normalized = {};
            levels.forEach(level => {
                const value = cells[level] ?? cells[String(level)] ?? [];
                normalized[level] = Array.isArray(value) && value.length ? value.map(v => String(v)) : [];
            });
            return normalized;
        },

        filteredDetailGroups() {
            if (!this.appFeatures.department) {
                return this.details.groups;
            }
            const keyword = (this.details.departmentSearch || '').toLowerCase();
            if (!keyword) {
                return this.details.groups;
            }
            return this.details.groups.filter(group => (group.department || '').toLowerCase().includes(keyword));
        },

        addLevelColumn() {
            const next = Math.max(...this.details.level_columns, 0) + 1;
            this.details.level_columns.push(next);
            this.details.groups.forEach(group => {
                group.subRows.forEach(subRow => {
                    subRow.cells[next] = [];
                });
            });
        },

        removeLevelColumn(level) {
            if (this.details.level_columns.length <= 1) {
                return;
            }
            this.details.level_columns = this.details.level_columns.filter(l => l !== level);
            this.details.groups.forEach(group => {
                group.subRows.forEach(subRow => delete subRow.cells[level]);
            });
        },

        addApprover(subRow, level) {
            if (!Array.isArray(subRow.cells[level])) {
                subRow.cells[level] = [];
            }
            subRow.cells[level].push('');
        },

        removeApprover(subRow, level, index) {
            subRow.cells[level].splice(index, 1);
            if (subRow.cells[level].length === 0) {
                subRow.cells[level] = [];
            }
        },

        createEmptySubRow() {
            const cells = {};
            this.details.level_columns.forEach(level => cells[level] = []);

            return {
                branch_id: '',
                type: 'direct',
                cells,
            };
        },

        addSubRow(group) {
            group.subRows.push(this.createEmptySubRow());
        },

        removeSubRow(groupIndex, subRowIndex) {
            const group = this.details.groups[groupIndex];
            if (!group) {
                return;
            }

            group.subRows.splice(subRowIndex, 1);
            if (group.subRows.length === 0) {
                this.details.groups.splice(groupIndex, 1);
            }
        },

        buildRowsPayload() {
            return this.details.groups
                .flatMap(group => group.subRows.map(subRow => ({ group, subRow })))
                .filter(({ group, subRow }) => {
                    const hasDept = this.appFeatures.department ? !!group.department : true;
                    const hasBranch = this.appFeatures.branch ? !!subRow.branch_id : true;
                    return hasDept && hasBranch;
                })
                .map(({ group, subRow }) => {
                    const levels = {};
                    this.details.level_columns.forEach(level => {
                        levels[String(level)] = (subRow.cells[level] || [])
                            .map(v => parseInt(v, 10))
                            .filter(v => v > 0);
                    });

                    return {
                        department: group.department || 'Default',
                        branch_id: this.appFeatures.branch ? parseInt(subRow.branch_id, 10) : 0,
                        type: subRow.type || 'direct',
                        levels,
                    };
                });
        },

        async saveDetails() {
            try {
                await this.request(`versions/${this.details.version.id}/mappings-levels`, {
                    method: 'PUT',
                    body: { rows: this.buildRowsPayload() },
                });
                this.successMessage = 'Version details saved.';
                await this.openDetailsModal(this.details.version);
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        openSaveAsNewModal() {
            this.saveAsNew = {
                open: true,
                new_version: `v${Date.now()}`,
                effective_from: new Date().toISOString().slice(0, 10),
                notes: '',
            };
        },

        async saveAsNewVersion() {
            try {
                await this.request('versions/save-as-new', {
                    method: 'POST',
                    body: {
                        old_version_id: this.details.version.id,
                        new_version: this.saveAsNew.new_version,
                        effective_from: this.saveAsNew.effective_from,
                        notes: this.saveAsNew.notes,
                        mappings: { rows: this.buildRowsPayload() },
                    },
                });
                this.saveAsNew.open = false;
                this.details.open = false;
                this.successMessage = 'New version created successfully.';
                await this.loadVersions(1);
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        async syncToModule(version) {
            try {
                const data = await this.request(`versions/${version.id}/sync-to-module`, { method: 'POST' });
                this.successMessage = data.message || 'Sync completed.';
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        tableColspan() {
            let cols = 5; // version, effective_from, effective_to, status, notes, actions = 6 always
            if (this.appFeatures.company) cols++;
            if (this.appFeatures.business_unit) cols++;
            if (this.appFeatures.module) cols++;
            return cols + 1; // +1 for actions
        },

        formatDate(value) {
            if (!value) return '-';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;
            return `${date.getMonth() + 1}/${date.getDate()}/${date.getFullYear()}`;
        },

        moduleLabel(version) {
            if (!version?.module) return '-';
            const name = version.module.name || '';
            const code = version.module.code || '';
            return code ? `${name} (${code})` : (name || '-');
        },

        paginationSummary() {
            if (!this.pagination.total) return 'Showing 0 entries';
            return `Showing ${this.pagination.from} to ${this.pagination.to} of ${this.pagination.total} entries`;
        },

        pageNumbers() {
            const pages = [];
            for (let i = 1; i <= this.pagination.last_page; i++) {
                pages.push(i);
            }
            return pages.slice(0, 8);
        },
    };
}
</script>
@endpush
