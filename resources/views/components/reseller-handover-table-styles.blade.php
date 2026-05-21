<style>
    .search-wrapper { position: relative; margin-bottom: 1.5rem; }
    .search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9ca3af; pointer-events: none; }
    .search-input { width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 0.875rem; transition: all 0.3s ease; background: white; }
    .search-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); }
    .table-container { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); overflow: hidden; }
    .custom-table { width: 100%; border-collapse: collapse; }
    .custom-table thead { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); }
    .custom-table th { padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid #e2e8f0; }
    .custom-table th button { display: flex; align-items: center; gap: 0.5rem; color: #64748b; font-weight: 600; transition: color 0.2s; background: none; border: none; cursor: pointer; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
    .custom-table th button:hover { color: #667eea; }
    .custom-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: all 0.2s ease; }
    .custom-table tbody tr:hover { background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%); }
    .custom-table td { padding: 1rem 1.5rem; font-size: 0.875rem; color: #1f2937; }
    .fb-id { font-weight: 600; color: #667eea; }
    .subscriber-name { font-weight: 600; color: #111827; }
    .date-cell { color: #6b7280; }
    .status-badge { display: inline-flex; padding: 0.375rem 0.75rem; font-size: 0.75rem; font-weight: 600; border-radius: 20px; letter-spacing: 0.025em; }
    .status-new { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46; border: 1px solid #6ee7b7; }
    .status-pending-quotation-confirmation { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; border: 1px solid #fcd34d; }
    .status-pending-invoice-confirmation { background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%); color: #991b1b; border: 1px solid #f87171; }
    .status-pending-reseller-payment { background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%); color: #991b1b; border: 1px solid #f87171; }
    .status-pending-timetec-license { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #3730a3; border: 1px solid #a5b4fc; }
    .status-pending-timetec-invoice { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #3730a3; border: 1px solid #a5b4fc; }
    .status-pending-timetec-finance { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #3730a3; border: 1px solid #a5b4fc; }
    .status-pending-finance-payment { background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%); color: #9a3412; border: 1px solid #fb923c; }
    .status-completed { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46; border: 1px solid #6ee7b7; }
    .status-inactive { background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); color: #4b5563; border: 1px solid #d1d5db; }
    .confirm-button { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 8px; font-size: 0.75rem; font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer; }
    .confirm-button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
    .cancel-order-button { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border-radius: 8px; font-size: 0.75rem; font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer; }
    .cancel-order-button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
    .confirm-icon { width: 1rem; height: 1rem; }
    .sort-icon { width: 1rem; height: 1rem; }
    .empty-state { padding: 3rem 1.5rem; text-align: center; color: #9ca3af; }
    .modal-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; animation: fadeIn 0.2s ease-out; }
    .modal-content { background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: slideUp 0.3s ease-out; }
    .modal-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
    .modal-icon { width: 3rem; height: 3rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .modal-icon svg { width: 1.5rem; height: 1.5rem; color: #d97706; }
    .modal-title { font-size: 1.25rem; font-weight: 700; color: #111827; }
    .modal-body { color: #6b7280; margin-bottom: 1.5rem; line-height: 1.6; }
    .modal-actions { display: flex; gap: 1rem; justify-content: flex-end; }
    .modal-button-cancel { padding: 0.625rem 1.25rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600; background: #f3f4f6; color: #374151; border: none; cursor: pointer; transition: all 0.2s; }
    .modal-button-cancel:hover { background: #e5e7eb; }
    .modal-button-confirm { padding: 0.625rem 1.25rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; cursor: pointer; transition: all 0.2s; }
    .modal-button-confirm:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
    .modal-button-danger { padding: 0.625rem 1.25rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none; cursor: pointer; transition: all 0.2s; }
    .modal-button-danger:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
    .success-message { position: fixed; top: 1rem; right: 1rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); z-index: 10000; animation: slideInRight 0.3s ease-out; }
    .filter-container { position: relative; display: inline-block; }
    .filter-button { display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.25rem; background: white; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s; font-size: 0.875rem; color: #64748b; white-space: nowrap; }
    .filter-button:hover { border-color: #667eea; color: #667eea; }
    .filter-button.active { background: #667eea; color: white; border-color: #667eea; }
    .filter-icon { width: 1rem; height: 1rem; }
    .filter-dropdown { position: absolute; top: 100%; right: 0; margin-top: 0.5rem; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); min-width: 260px; z-index: 9999; display: none; }
    .filter-dropdown.show { display: block; }
    .filter-section { padding: 0.75rem; border-bottom: 1px solid #e5e7eb; }
    .filter-section:last-child { border-bottom: none; }
    .filter-section-title { font-size: 0.75rem; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; }
    .filter-select-wrapper { padding: 0.75rem; }
    .filter-select { width: 100%; padding: 0.625rem 2.5rem 0.625rem 0.75rem; background: white; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s; font-size: 0.875rem; color: #374151; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.25rem; }
    .filter-select:hover { border-color: #667eea; }
    .filter-select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
    .form-group { margin-bottom: 1.5rem; }
    .form-label { display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
    .form-label.required::after { content: ' *'; color: #ef4444; }
    .file-upload-wrapper { position: relative; border: 2px dashed #d1d5db; border-radius: 12px; padding: 1rem; background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%); transition: all 0.3s ease; cursor: pointer; }
    .file-upload-wrapper:hover { border-color: #667eea; background: linear-gradient(135deg, #f0f4ff 0%, #ffffff 100%); }
    .file-upload-wrapper.has-file { border-color: #10b981; background: linear-gradient(135deg, #d1fae5 0%, #ffffff 100%); border-style: solid; pointer-events: none; cursor: not-allowed; }
    .file-upload-content { text-align: center; }
    .file-upload-text { font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
    .file-upload-hint { font-size: 0.75rem; color: #9ca3af; }
    .file-upload-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .file-selected-info { display: flex; align-items: center; gap: 0.75rem; margin-top: 1rem; padding: 0.75rem; background: white; border-radius: 8px; border: 1px solid #d1fae5; }
    .file-selected-details { flex: 1; }
    .file-selected-name { font-size: 0.875rem; font-weight: 600; color: #059669; word-break: break-all; }
    .file-selected-size { font-size: 0.75rem; color: #6b7280; margin-top: 0.125rem; }
    .file-delete-button { padding: 0.375rem; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 6px; border: none; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; flex-shrink: 0; pointer-events: auto; }
    .file-delete-button:hover { transform: scale(1.1); box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3); }
    .file-delete-button svg { width: 1rem; height: 1rem; color: white; }
    .error-text { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes slideInRight { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
</style>
