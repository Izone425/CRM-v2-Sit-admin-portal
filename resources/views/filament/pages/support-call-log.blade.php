<x-filament::page>
    <style>
        /* Summary cards styling */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr) auto;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: stretch;
        }

        .summary-card {
            padding: 1.25rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }

        .card-total { background: linear-gradient(to bottom right, #ebf5ff, #dbeafe); border: 1px solid #bfdbfe; }
        .card-completed { background: linear-gradient(to bottom right, #ecfdf5, #d1fae5); border: 1px solid #a7f3d0; }
        .card-pending { background: linear-gradient(to bottom right, #fee2e2, #fecaca); border: 1px solid #fca5a5; }
        .card-time { background: linear-gradient(to bottom right, #fffbeb, #fef3c7); border: 1px solid #fde68a; }

        .card-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .card-total .card-value { color: #2563eb; }
        .card-completed .card-value { color: #059669; }
        .card-pending .card-value { color: #dc2626; }
        .card-time .card-value { color: #d97706; }

        .card-label {
            font-size: 0.875rem;
            color: #4b5563;
            font-weight: 500;
        }

        .period-card { cursor: default; display: flex; flex-direction: column; gap: 0.35rem; }
        .period-card:hover { transform: none; }
        .period-card .period-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        .card-total.period-card .period-title     { color: #1d4ed8; }
        .card-completed.period-card .period-title { color: #047857; }
        .card-pending.period-card .period-title   { color: #b91c1c; }
        .card-time.period-card .period-title      { color: #b45309; }
        .period-card .period-row {
            font-size: 0.85rem;
            color: #4b5563;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }
        .period-card .period-row strong {
            color: #111827;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .group-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            /* width: 1.5rem;
            height: 1.5rem;
            background-color: #2563eb; */
            color: red;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 9999px;
            margin-right: 0.5rem;
        }

        .staff-number {
            font-size: 1.5rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            min-width: 3rem;
            text-align: center;
        }

        .staff-number-total {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .staff-number-completed {
            background-color: #d1fae5;
            color: #059669;
        }

        .staff-number-pending {
            background-color: #fee2e2;
            color: #dc2626;
        }

        /* Update the staff-name to not have a margin-bottom since they're on the same line now */
        .staff-name {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0; /* Changed from 0.5rem */
        }

        .slide-over-overlay {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 99999 !important;
        }

        .slide-over-modal {
            position: fixed !important; /* Change from relative to fixed */
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            max-width: 500px !important;
            height: 100vh !important;
            background-color: white;
            box-shadow: -4px 0 24px rgba(0, 0, 0, 0.25);
            z-index: 100000 !important; /* Extremely high z-index */
            border-radius: 12px 0 0 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .slide-over-header {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 100001 !important; /* Even higher than modal */
            border-bottom: 1px solid #e5e7eb;
            padding: 1.25rem 1.5rem;
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .slide-over-content {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            padding-bottom: 80px;
        }

        .staff-stats-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-left: 4px solid #3b82f6;
        }

        .staff-name {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }

        .staff-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }

        .stat-item {
            background-color: #f9fafb;
            padding: 0.75rem;
            border-radius: 0.375rem;
            text-align: center;
        }

        .stat-item-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #3b82f6;
        }

        .stat-item-label {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        @media (max-width: 1024px) {
            .summary-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 640px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .staff-stats {
                grid-template-columns: 1fr;
            }
        }

        .staff-stats-card {
            background-color: white;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            padding: 0.5rem 1rem;
            margin-bottom: 0.5rem;
            border-left: 3px solid #3b82f6;
        }

        .staff-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0;
        }

        .staff-number {
            font-size: 1rem;
            font-weight: 700;
            padding: 0.125rem 0.5rem;
            border-radius: 0.375rem;
            min-width: 2.5rem;
            text-align: center;
        }

        .staff-number-time {
            /* background-color: #fef3c7; */
            color: #d97706;
            font-size: 0.875rem;
            font-weight: 700;
            padding: 0.125rem 0.5rem;
            border-radius: 0.375rem;
            min-width: 2.5rem;
            text-align: center;
        }

        .total-time-day {
            background-color: #c8c7fe;
            color: #0637d9;
            font-size: 0.875rem;
            font-weight: 700;
            padding: 0.125rem 0.5rem;
            border-radius: 0.375rem;
            min-width: 2.5rem;
            text-align: center;
        }

        .slide-over-total {
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
            border-radius: 0.375rem;
        }

        .slide-over-total-medium {
            background-color: rgba(217, 119, 6, 0.1);
            border: 1px solid rgba(217, 119, 6, 0.2);
        }

        .slide-over-total-label {
            font-size: 0.875rem;
            color: rgb(107, 114, 128);
        }

        .slide-over-total-value {
            margin-left: 0.25rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: rgb(55, 65, 81);
        }

        /* === Support Summary modal styling === */
        .support-sort-toggle {
            display: inline-flex;
            background: #f3f4f6;
            border-radius: 10px;
            padding: 4px;
            margin-bottom: 16px;
            gap: 2px;
        }
        .support-sort-btn {
            padding: 7px 16px;
            border: none;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            background: transparent;
            color: #6b7280;
            transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
            position: relative;
            z-index: 1;
            pointer-events: auto;
        }
        .support-sort-btn.is-active {
            background: #fff;
            color: #111827;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .support-sort-btn:not(.is-active):hover {
            color: #374151;
        }

        .support-rank-card {
            position: relative;
            display: flex;
            flex-direction: column;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            transition: box-shadow 0.15s ease, border-color 0.15s ease;
            overflow: hidden;
            cursor: pointer;
        }
        .support-rank-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border-color: #d1d5db;
        }
        .support-rank-card.is-top {
            background: linear-gradient(135deg, #fffbeb 0%, #ffffff 65%);
            border-color: #fde68a;
        }
        .support-rank-card.is-expanded {
            border-color: #c7d2fe;
            box-shadow: 0 6px 18px rgba(67,56,202,0.08);
        }
        .support-rank-card-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
        }
        .support-expand-chevron {
            color: #9ca3af;
            transition: transform 0.2s ease;
            flex-shrink: 0;
        }
        .support-rank-card.is-expanded .support-expand-chevron {
            transform: rotate(180deg);
            color: #4338ca;
        }
        .support-rank-chart {
            padding: 8px 14px 12px 14px;
            border-top: 1px dashed #e5e7eb;
            background: rgba(249, 250, 251, 0.6);
        }
        .support-rank-chart .chart-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .support-rank-chart .chart-meta strong {
            color: #4338ca;
            font-weight: 600;
        }
        .support-rank-chart .chart-labels {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #9ca3af;
            margin-top: 2px;
            font-variant-numeric: tabular-nums;
        }
        .support-rank-chart-empty {
            text-align: center;
            padding: 8px;
            color: #9ca3af;
            font-size: 11px;
        }
        .support-rank-chart-clickhint {
            font-size: 10px;
            color: #6366f1;
            text-align: right;
            margin-top: 4px;
            font-weight: 500;
            letter-spacing: 0.02em;
        }
        .support-rank-chart-clickhint:hover { text-decoration: underline; }

        /* === Full chart modal === */
        .full-chart-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 100002;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .full-chart-modal {
            background: #fff;
            border-radius: 14px;
            max-width: 760px;
            width: 100%;
            max-height: 85vh;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.35);
            display: flex;
            flex-direction: column;
        }
        .full-chart-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 22px;
            border-bottom: 1px solid #e5e7eb;
        }
        .full-chart-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }
        .full-chart-header p {
            margin: 4px 0 0;
            font-size: 12px;
            color: #6b7280;
        }
        .full-chart-close {
            padding: 4px 10px;
            font-size: 22px;
            line-height: 1;
            background: none;
            border: none;
            cursor: pointer;
            color: #6b7280;
            border-radius: 6px;
        }
        .full-chart-close:hover { background: #f3f4f6; color: #111827; }
        .full-chart-body {
            padding: 18px 22px 22px;
            overflow-y: auto;
        }
        .full-chart-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 14px;
        }
        .full-chart-stat {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 12px;
        }
        .full-chart-stat-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
        }
        .full-chart-stat-value {
            margin-top: 3px;
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            font-variant-numeric: tabular-nums;
        }
        .full-chart-svg-wrap {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
            position: relative;
        }
        .full-chart-inner {
            position: relative;
            width: 100%;
        }
        .full-chart-inner svg {
            display: block;
            width: 100%;
            height: 100%;
        }
        .full-chart-y-label {
            position: absolute;
            font-size: 11px;
            font-weight: 500;
            color: #374151;
            text-align: right;
            transform: translateY(-50%);
            pointer-events: none;
            white-space: nowrap;
        }
        .full-chart-x-label {
            position: absolute;
            font-size: 11px;
            font-weight: 500;
            color: #374151;
            transform: translateX(-50%);
            pointer-events: none;
            white-space: nowrap;
        }
        .full-chart-axis-title {
            position: absolute;
            font-size: 12px;
            font-weight: 700;
            color: #1f2937;
            pointer-events: none;
        }
        .full-chart-tooltip {
            position: absolute;
            background: #111827;
            color: #fff;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 11px;
            line-height: 1.4;
            transform: translate(-50%, calc(-100% - 14px));
            pointer-events: none;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 5;
        }
        .full-chart-tooltip::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 50%;
            transform: translateX(-50%) rotate(45deg);
            width: 8px;
            height: 8px;
            background: #111827;
        }
        .full-chart-tooltip-label {
            font-weight: 700;
        }
        .full-chart-empty {
            text-align: center;
            padding: 60px 20px 50px;
            color: #9ca3af;
        }
        .full-chart-empty svg {
            color: #d1d5db;
            margin-bottom: 14px;
        }
        .full-chart-empty-title {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            color: #4b5563;
        }
        .full-chart-empty-sub {
            margin: 6px 0 0;
            font-size: 12px;
            color: #9ca3af;
        }

        .support-rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
            flex-shrink: 0;
        }
        .support-rank-badge.rank-1 { background: linear-gradient(135deg, #fcd34d, #f59e0b); }
        .support-rank-badge.rank-2 { background: linear-gradient(135deg, #e2e8f0, #94a3b8); }
        .support-rank-badge.rank-3 { background: linear-gradient(135deg, #fbbf24, #b45309); }
        .support-rank-badge.rank-other { background: linear-gradient(135deg, #818cf8, #6366f1); }

        .support-rank-name {
            flex: 1;
            font-weight: 600;
            color: #111827;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
        }
        .support-rank-name .crown-icon {
            color: #f59e0b;
            flex-shrink: 0;
        }

        .support-metric-count {
            display: inline-flex;
            align-items: center;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-weight: 600;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .support-metric-count.is-active {
            color: #1d4ed8;
            background: #dbeafe;
        }
        .support-metric-count.is-inactive {
            color: #6b7280;
            background: #f3f4f6;
        }

        .support-metric-duration {
            font-size: 12px;
            font-variant-numeric: tabular-nums;
            min-width: 78px;
            text-align: right;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.15s ease;
        }
        .support-metric-duration.is-active {
            color: #4338ca;
            background: #e0e7ff;
        }
        .support-metric-duration.is-inactive {
            color: #6b7280;
            background: transparent;
        }

        .support-metric-bar {
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            background: linear-gradient(90deg, #60a5fa, #3b82f6);
            transition: width 0.4s ease;
            border-top-right-radius: 3px;
        }
        .support-rank-card.sort-duration .support-metric-bar {
            background: linear-gradient(90deg, #a5b4fc, #4338ca);
        }

        .implementer-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            margin-bottom: 0.25rem;
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            transition: all 0.2s;
            font-size: 0.875rem;
            cursor: pointer;  /* Add cursor pointer since it's clickable now */
        }

        .implementer-name {
            font-weight: 600;
            color: rgb(55, 65, 81);
            display: flex;
            align-items: center;
        }

        .implementer-name svg {
            margin-left: 0.5rem;
            width: 0.875rem;
            height: 0.875rem;
            transition: transform 0.15s ease;
        }

        .implementer-name.expanded svg {
            transform: rotate(180deg);
        }

        .date-list {
            margin-top: 0.25rem;
            margin-bottom: 1rem;
            margin-left: 1rem;
            padding-left: 0.5rem;
            border-left: 2px solid #e5e7eb;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .date-list.expanded {
            max-height: 1000px;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .date-list table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .date-list th {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
            text-align: left;
            padding: 0.25rem 0.5rem;
        }

        .date-list td {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .date-list tr:last-child td {
            border-bottom: none;
        }

        /* Make sure the expanded state works correctly with the table */
        .date-list.expanded {
            max-height: 1500px; /* Increased to accommodate table */
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        /* Swap arrow button */
        .swap-arrow-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 60px;
            padding: 1rem;
            border-radius: 0.5rem;
            background: linear-gradient(to bottom right, #f3f4f6, #e5e7eb);
            border: 1px solid #d1d5db;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .swap-arrow-btn:hover {
            background: linear-gradient(to bottom right, #e5e7eb, #d1d5db);
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }

        .swap-arrow-btn svg {
            width: 24px;
            height: 24px;
            color: #4b5563;
            transition: transform 0.3s ease;
        }

        .swap-arrow-btn:hover svg {
            color: #3b82f6;
        }

        .swap-arrow-btn.to-extension svg {
            transform: rotate(0deg);
        }

        .swap-arrow-btn.to-logs svg {
            transform: rotate(180deg);
        }

        /* Extension status grid - horizontal scroll */
        .extension-grid {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        .extension-grid::-webkit-scrollbar {
            height: 6px;
        }

        .extension-grid::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .extension-grid::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .extension-grid::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .extension-card {
            background: white;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            width: 205px;
            flex-shrink: 0;
            text-align: center;
            display: flex;
            flex-direction: column;
            min-height: 120px;
        }

        .extension-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .extension-card .status-indicator {
            position: absolute;
            top: 0;
            right: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 40px 40px 0;
        }

        .extension-card.status-available .status-indicator {
            border-color: transparent #10b981 transparent transparent;
        }

        .extension-card.status-in-use .status-indicator {
            border-color: transparent #f59e0b transparent transparent;
        }

        .extension-card.status-unavailable .status-indicator {
            border-color: transparent #ef4444 transparent transparent;
        }

        .extension-card.status-ringing .status-indicator {
            border-color: transparent #8b5cf6 transparent transparent;
        }

        .extension-card .ext-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .extension-card .ext-name {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 0.5rem;
            flex: 1;
        }

        .extension-card .ext-status {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .extension-card .ext-status.available {
            background: #d1fae5;
            color: #047857;
        }

        .extension-card .ext-status.in-use {
            background: #fef3c7;
            color: #b45309;
        }

        .extension-card .ext-status.unavailable {
            background: #fee2e2;
            color: #b91c1c;
        }

        .extension-card .ext-status.ringing {
            background: #ede9fe;
            color: #6d28d9;
        }

        .extension-card .ext-status .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .extension-card .ext-status.available .status-dot { background: #10b981; }
        .extension-card .ext-status.in-use .status-dot { background: #f59e0b; animation: pulse-dot 1.5s infinite; }
        .extension-card .ext-status.unavailable .status-dot { background: #ef4444; }
        .extension-card .ext-status.ringing .status-dot { background: #8b5cf6; animation: pulse-dot 0.5s infinite; }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .extension-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .extension-header h2 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
        }

        .refresh-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .refresh-btn:hover {
            background: #2563eb;
        }

        .extension-summary {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .ext-summary-card {
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .ext-summary-card.available {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border: 1px solid #a7f3d0;
        }

        .ext-summary-card.in-use {
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            border: 1px solid #fde68a;
        }

        .ext-summary-card.unavailable {
            background: linear-gradient(135deg, #fef2f2, #fecaca);
            border: 1px solid #fca5a5;
        }

        .ext-summary-card .count {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .ext-summary-card.available .count { color: #059669; }
        .ext-summary-card.in-use .count { color: #d97706; }
        .ext-summary-card.unavailable .count { color: #dc2626; }

        .ext-summary-card .label {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
        }
    </style>

    <div class="mb-6">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 0.75rem;">
            <h2 class="section-title" style="margin: 0;">Call Log List</h2>

            @if($activeTab === 'extension_status')
                <button
                    type="button"
                    wire:click="openEditOrderModal"
                    style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.9rem; border-radius: 0.5rem; background: #4f46e5; color: white; font-weight: 600; font-size: 0.85rem; border: none; cursor: pointer;"
                    title="Edit display order"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Order
                </button>
            @endif
        </div>

        {{-- TOP SECTION: Swaps between Summary Cards and Extension Status Cards --}}
        @if($activeTab === 'call_logs')
        <div class="summary-grid">
            @php
                // Get support staff extensions dynamically
                $supportExtensions = \App\Models\PhoneExtension::where('is_support_staff', true)
                    ->where('is_active', true)
                    ->pluck('extension')
                    ->toArray();

                $receptionExtension = \App\Models\PhoneExtension::where('extension', '100')
                    ->value('extension') ?? '100';

                $baseCallQuery = function () use ($supportExtensions, $receptionExtension) {
                    return \App\Models\CallLog::query()
                        ->where(function ($query) use ($supportExtensions, $receptionExtension) {
                            $query->whereIn('caller_number', array_merge([$receptionExtension], $supportExtensions))
                                ->orWhereIn('receiver_number', $supportExtensions);
                        })
                        ->where('call_status', '!=', 'NO ANSWER')
                        ->where(function ($query) {
                            $query->where('call_duration', '>=', 5)
                                ->orWhereNull('call_duration');
                        });
                };

                $now = now();
                $periods = [
                    ['title' => 'Total All',  'class' => 'card-total',     'filter' => null],
                    ['title' => 'This Year',  'class' => 'card-completed', 'filter' => fn($q) => $q->whereYear('started_at', $now->year)],
                    ['title' => 'This Month', 'class' => 'card-pending',   'filter' => fn($q) => $q->whereYear('started_at', $now->year)->whereMonth('started_at', $now->month)],
                    ['title' => 'Today',      'class' => 'card-time',      'filter' => fn($q) => $q->whereDate('started_at', $now->toDateString())],
                ];

                $formatHm = function ($seconds) {
                    $h = (int) floor($seconds / 3600);
                    $m = (int) floor(($seconds % 3600) / 60);
                    return "{$h}h {$m}m";
                };
            @endphp

            @foreach($periods as $period)
                @php
                    $q = $baseCallQuery();
                    if ($period['filter']) ($period['filter'])($q);
                    $count = (clone $q)->count();
                    $duration = (clone $q)->sum('call_duration');
                @endphp
                <div class="summary-card {{ $period['class'] }} period-card">
                    <div class="period-title">{{ $period['title'] }}</div>
                    <div class="period-row"><span>Phone Call:</span> <strong>{{ $count }}</strong></div>
                    <div class="period-row"><span>Duration:</span> <strong>{{ $formatHm($duration) }}</strong></div>
                </div>
            @endforeach

            <!-- Swap Arrow Button -->
            <button
                wire:click="toggleTab"
                class="swap-arrow-btn to-extension"
                title="View Extension Status"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
        @endif

        @if($activeTab === 'extension_status')
        <div class="extension-row" style="display: flex; align-items: stretch; gap: 1rem;" wire:poll.10s="loadExtensionStatuses">
            @if(count($extensionStatuses) > 0)
                <div
                    class="extension-grid"
                    style="flex: 1;"
                    x-data="{
                        sortableInstance: null,
                        async initSortable() {
                            if (!window.Sortable) {
                                await new Promise(r => {
                                    const s = document.createElement('script');
                                    s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
                                    s.onload = r;
                                    document.head.appendChild(s);
                                });
                            }
                            if (this.sortableInstance) this.sortableInstance.destroy();
                            this.sortableInstance = Sortable.create($el, {
                                animation: 150,
                                ghostClass: 'sortable-ghost',
                                onEnd: () => {
                                    const ids = Array.from($el.querySelectorAll('[data-id]'))
                                        .map(n => parseInt(n.dataset.id, 10))
                                        .filter(Number.isFinite);
                                    $wire.call('updateExtensionOrder', ids);
                                },
                            });
                        }
                    }"
                    x-init="initSortable()"
                    @morph.window="initSortable()"
                >
                    @foreach($extensionStatuses as $ext)
                        @php
                            $statusClass = match($ext['deviceState']) {
                                'Not in use' => 'available',
                                'In use', 'On hold' => 'in-use',
                                'Ringing' => 'ringing',
                                default => 'unavailable'
                            };
                            $statusLabel = match($ext['deviceState']) {
                                'Not in use' => 'Available',
                                'In use' => 'In Use',
                                'On hold' => 'On Hold',
                                'Ringing' => 'Ringing',
                                'Unavailable' => 'Offline',
                                default => $ext['deviceState']
                            };
                        @endphp
                        <div
                            wire:key="ext-card-{{ $ext['id'] }}"
                            class="extension-card status-{{ $statusClass }}"
                            data-id="{{ $ext['id'] }}"
                            style="cursor: grab;"
                        >
                            <div class="status-indicator"></div>
                            <div class="ext-number">{{ $ext['extension'] }}</div>
                            <div class="ext-name">{{ $ext['name'] }}</div>
                            <div class="ext-status {{ $statusClass }}">
                                <span class="status-dot"></span>
                                {{ $statusLabel }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-gray-500" style="flex: 1;">
                    <p>Loading extension status...</p>
                </div>
            @endif

            <!-- Swap Arrow Button (back to call logs) -->
            <button
                wire:click="toggleTab"
                class="swap-arrow-btn to-logs"
                title="View Call Logs Summary"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
        @endif

    </div>

    {{-- Edit Sort Order Modal — placed OUTSIDE main content + teleported to body --}}
    @if($showEditOrderModal)
    <template x-teleport="body">
        <div
            x-data="{
                sortableInstance: null,
                async initSortable() {
                    await this.$nextTick();
                    const el = document.getElementById('edit-order-list');
                    if (!el) return;
                    if (!window.Sortable) {
                        await new Promise(r => {
                            const s = document.createElement('script');
                            s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
                            s.onload = r;
                            document.head.appendChild(s);
                        });
                    }
                    if (this.sortableInstance) this.sortableInstance.destroy();
                    this.sortableInstance = Sortable.create(el, {
                        animation: 150,
                        handle: '.drag-handle',
                        ghostClass: 'sort-ghost',
                    });
                },
                apply() {
                    const el = document.getElementById('edit-order-list');
                    if (!el) return;
                    const ids = Array.from(el.querySelectorAll('[data-id]'))
                        .map(n => parseInt(n.dataset.id, 10))
                        .filter(Number.isFinite);
                    $wire.call('applyExtensionOrder', ids);
                },
            }"
            x-init="initSortable()"
            class="edit-order-overlay"
            @keydown.escape.window="$wire.call('closeEditOrderModal')"
        >
            <div class="edit-order-modal" @click.outside="$wire.call('closeEditOrderModal')">
                <div class="edit-order-header">
                    <h3>Edit Display Order</h3>
                    <button type="button" wire:click="closeEditOrderModal" class="edit-order-close">&times;</button>
                </div>

                <div class="edit-order-hint">
                    Drag the rows to reorder. Click <strong>Apply</strong> to save.
                </div>

                <ul id="edit-order-list" class="edit-order-list">
                    @foreach($editOrderList as $idx => $row)
                        <li
                            wire:key="edit-row-{{ $row['id'] }}"
                            data-id="{{ $row['id'] }}"
                        >
                            <span class="drag-handle">⋮⋮</span>
                            <span class="ext-num">{{ $row['extension'] }}</span>
                            <span class="ext-name-text">{{ $row['name'] }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="edit-order-footer">
                    <button type="button" wire:click="closeEditOrderModal" class="btn-cancel">Cancel</button>
                    <button type="button" @click="apply()" class="btn-apply">Apply</button>
                </div>
            </div>
        </div>
    </template>
    @endif

    <style>
        [x-cloak] { display: none !important; }
        .sort-ghost { opacity: 0.4; background: #e0e7ff !important; }

        .edit-order-overlay {
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            background: rgba(0, 0, 0, 0.5) !important;
            z-index: 99999 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 1rem !important;
        }
        .edit-order-modal {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 460px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .edit-order-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .edit-order-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
        }
        .edit-order-close {
            background: none;
            border: none;
            cursor: pointer;
            color: #6b7280;
            font-size: 1.5rem;
            line-height: 1;
            padding: 0 0.25rem;
        }
        .edit-order-hint {
            padding: 0.75rem 1.25rem;
            font-size: 0.8rem;
            color: #6b7280;
            background: #f9fafb;
        }
        .edit-order-list {
            list-style: none;
            margin: 0;
            padding: 0.75rem;
            overflow-y: auto;
            flex: 1;
        }
        .edit-order-list li {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 0.75rem;
            margin-bottom: 0.4rem;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        .edit-order-list .drag-handle {
            cursor: grab;
            color: #9ca3af;
            user-select: none;
            font-size: 1rem;
        }
        .edit-order-list .ext-num {
            font-weight: 600;
            color: #4f46e5;
            min-width: 3rem;
        }
        .edit-order-list .ext-name-text {
            color: #111827;
        }
        .edit-order-footer {
            padding: 0.85rem 1.25rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            background: #f9fafb;
        }
        .btn-cancel {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            font-weight: 500;
            cursor: pointer;
        }
        .btn-apply {
            padding: 0.5rem 1.1rem;
            border-radius: 6px;
            border: none;
            background: #4f46e5;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }
    </style>

    {{-- Slide Over Panel for Generated Summary --}}
    <template x-teleport="body">
        <div
            x-data="{
                open: @entangle('showSummaryModal'),
                sortBy: 'count',
                rows: @entangle('summaryResults'),
                expanded: {},
                get sortedRows() {
                    return [...this.rows].sort((a, b) =>
                        this.sortBy === 'duration'
                            ? (b.duration - a.duration) || (b.count - a.count)
                            : (b.count - a.count) || (b.duration - a.duration)
                    );
                },
                get maxCount() { return Math.max(...this.rows.map(r => r.count), 1); },
                get maxDuration() { return Math.max(...this.rows.map(r => r.duration), 1); },
                chartPoints(series) {
                    if (!series || !series.length) return [];
                    const max = Math.max(...series.map(d => d.count), 1);
                    const n = series.length;
                    const w = 300, h = 50, top = 5;
                    return series.map((d, idx) => ({
                        x: n === 1 ? w / 2 : (idx / (n - 1)) * w,
                        y: top + (h - (d.count / max) * h),
                        count: d.count,
                        date: d.date,
                        label: d.label,
                    }));
                },
                chartPath(series) {
                    return this.chartPoints(series).map(p => p.x + ',' + p.y).join(' ');
                },
                chartArea(series) {
                    const pts = this.chartPoints(series);
                    if (!pts.length) return '';
                    const first = pts[0], last = pts[pts.length - 1];
                    return 'M' + first.x + ',55 L' + pts.map(p => p.x + ',' + p.y).join(' L') + ' L' + last.x + ',55 Z';
                },
                peakInfo(series) {
                    if (!series || !series.length) return null;
                    const peak = series.reduce((a, b) => (b.count > a.count ? b : a), series[0]);
                    return peak.count > 0 ? peak : null;
                },
                focusedRow: null,
                hoverPoint: null,
                openFullChart(row) {
                    this.focusedRow = row;
                    this.hoverPoint = null;
                },
                fullChartDims: { w: 600, h: 220, padL: 40, padR: 16, padT: 16, padB: 36 },
                fullChartPoints(series) {
                    if (!series || !series.length) return [];
                    const d = this.fullChartDims;
                    const innerW = d.w - d.padL - d.padR;
                    const innerH = d.h - d.padT - d.padB;
                    const max = Math.max(...series.map(p => p.count), 1);
                    const n = series.length;
                    return series.map((p, idx) => ({
                        x: d.padL + (n === 1 ? innerW / 2 : (idx / (n - 1)) * innerW),
                        y: d.padT + (innerH - (p.count / max) * innerH),
                        count: p.count,
                        date: p.date,
                        label: p.label,
                    }));
                },
                fullChartPath(series) {
                    return this.fullChartPoints(series).map(p => p.x + ',' + p.y).join(' ');
                },
                fullChartArea(series) {
                    const pts = this.fullChartPoints(series);
                    if (!pts.length) return '';
                    const baseY = this.fullChartDims.h - this.fullChartDims.padB;
                    return 'M' + pts[0].x + ',' + baseY + ' L' + pts.map(p => p.x + ',' + p.y).join(' L') + ' L' + pts[pts.length - 1].x + ',' + baseY + ' Z';
                },
                fullChartYTicks(series) {
                    if (!series || !series.length) return [];
                    const max = Math.max(...series.map(p => p.count), 1);
                    const d = this.fullChartDims;
                    const innerH = d.h - d.padT - d.padB;
                    const ticks = 4;
                    return Array.from({length: ticks + 1}, (_, i) => ({
                        value: Math.round((max * i) / ticks),
                        y: d.padT + innerH - (innerH * i / ticks),
                    }));
                },
                fullChartXLabels(series) {
                    const pts = this.fullChartPoints(series);
                    const n = pts.length;
                    if (!n) return [];
                    const step = Math.max(1, Math.ceil(n / 8));
                    return pts.filter((_, idx) => idx === 0 || idx === n - 1 || idx % step === 0);
                },
                fullChartHitRects(series) {
                    const pts = this.fullChartPoints(series);
                    const n = pts.length;
                    if (!n) return [];
                    const d = this.fullChartDims;
                    const innerW = d.w - d.padL - d.padR;
                    const segW = n > 1 ? innerW / (n - 1) : innerW;
                    return pts.map(p => ({ ...p, hitX: p.x - segW / 2, hitW: segW }));
                },
                escapeXml(s) {
                    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                },
                yAxisHtml(series) {
                    return this.fullChartYTicks(series).map(t => `<line x1='40' y1='${t.y}' x2='584' y2='${t.y}' stroke='#e5e7eb' stroke-width='1'></line><line x1='35' y1='${t.y}' x2='40' y2='${t.y}' stroke='#6b7280' stroke-width='1'></line><text x='31' y='${t.y + 4}' text-anchor='end' fill='#374151' font-size='11' font-weight='500'>${this.escapeXml(t.value)}</text>`).join('');
                },
                xAxisHtml(series) {
                    return this.fullChartXLabels(series).map(p => `<line x1='${p.x}' y1='184' x2='${p.x}' y2='189' stroke='#6b7280' stroke-width='1'></line><text x='${p.x}' y='203' text-anchor='middle' fill='#374151' font-size='11' font-weight='500'>${this.escapeXml(p.label)}</text>`).join('');
                },
                chartPointsHtml(series) {
                    const peak = this.peakInfo(series);
                    return this.fullChartPoints(series).map(p => {
                        const isPeak = peak && peak.date === p.date;
                        return `<circle cx='${p.x}' cy='${p.y}' r='${isPeak ? 4 : 2.5}' fill='${isPeak ? '#f59e0b' : '#4338ca'}' stroke='#fff' stroke-width='1.2' style='pointer-events: none;'></circle>`;
                    }).join('');
                },
                hoverFromMouse(event, series) {
                    if (!series || !series.length) { this.hoverPoint = null; return; }
                    const target = event.currentTarget || event.target;
                    const svg = target.ownerSVGElement || target;
                    if (!svg || !svg.createSVGPoint) return;
                    const pt = svg.createSVGPoint();
                    pt.x = event.clientX;
                    pt.y = event.clientY;
                    const ctm = svg.getScreenCTM();
                    if (!ctm) return;
                    const local = pt.matrixTransform(ctm.inverse());
                    const points = this.fullChartPoints(series);
                    let closest = points[0];
                    let minDist = Math.abs(local.x - closest.x);
                    for (let i = 1; i < points.length; i++) {
                        const d = Math.abs(local.x - points[i].x);
                        if (d < minDist) { minDist = d; closest = points[i]; }
                    }
                    this.hoverPoint = closest;
                },
                sumCalls(series) { return (series || []).reduce((s, d) => s + d.count, 0); },
                activeDays(series) { return (series || []).filter(d => d.count > 0).length; },
                avgPerBucket(series) {
                    if (!series || !series.length) return '0';
                    const total = this.sumCalls(series);
                    const avg = total / series.length;
                    return (Math.round(avg * 10) / 10).toString();
                }
            }"
            x-init="$watch('open', v => { if (v) { sortBy = 'count'; expanded = {}; focusedRow = null; } })"
            @keydown.window.escape="focusedRow ? (focusedRow = null) : (open && (open = false))"
        >
            <div
                x-show="open"
                class="slide-over-overlay"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                style="display: none;"
            >
            <div class="slide-over-modal" @click.away="if (!focusedRow && !$event.target.closest('.full-chart-overlay')) open = false">
                <div class="slide-over-header">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Support Summary</h2>
                        <p class="text-xs text-gray-500" style="margin-top: 2px;">
                            @php
                                $f = $summaryFilters;
                                $rangeStart = $f['from'] ? \Carbon\Carbon::parse($f['from'])->format('d M Y') : null;
                                $rangeEnd = $f['until'] ? \Carbon\Carbon::parse($f['until'])->format('d M Y') : null;
                                if ($rangeStart && $rangeEnd) $range = "Date: {$rangeStart} – {$rangeEnd}";
                                elseif ($rangeStart) $range = "Date: from {$rangeStart}";
                                elseif ($rangeEnd) $range = "Date: until {$rangeEnd}";
                                else $range = "All time";
                            @endphp
                            {{ $range }}
                            @if(!empty($f['support'])) · Support: <strong>{{ $f['support'] }}</strong> @endif
                        </p>
                    </div>
                    <button wire:click="closeSummaryModal" class="p-1 text-2xl leading-none text-gray-500 hover:text-gray-700">&times;</button>
                </div>

                <div class="slide-over-content">
                    {{-- Sort toggle --}}
                    <div class="support-sort-toggle" wire:ignore>
                        <button type="button" @click.prevent="sortBy = 'count'"
                            class="support-sort-btn"
                            :class="{ 'is-active': sortBy === 'count' }">
                            Highest Count
                        </button>
                        <button type="button" @click.prevent="sortBy = 'duration'"
                            class="support-sort-btn"
                            :class="{ 'is-active': sortBy === 'duration' }">
                            Highest Duration
                        </button>
                    </div>

                    <template x-if="sortedRows.length === 0">
                        <div style="padding: 24px; text-align: center; color: #9ca3af;">
                            No calls match the selected filters.
                        </div>
                    </template>

                    <div x-show="sortedRows.length > 0" style="display: flex; flex-direction: column; gap: 10px;">
                        <template x-for="(row, i) in sortedRows" :key="row.name">
                            <div class="support-rank-card"
                                :class="{ 'is-top': i === 0, 'sort-duration': sortBy === 'duration', 'is-expanded': expanded[row.name] }"
                                @click="expanded[row.name] = !expanded[row.name]">
                                <div class="support-rank-card-row">
                                    <span class="support-rank-badge"
                                        :class="i === 0 ? 'rank-1' : (i === 1 ? 'rank-2' : (i === 2 ? 'rank-3' : 'rank-other'))"
                                        x-text="i + 1"></span>
                                    <span class="support-rank-name">
                                        <span x-text="row.name" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></span>
                                        <svg x-show="i === 0" class="crown-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M5 16L3 5l5.5 4L12 4l3.5 5L21 5l-2 11H5zm0 3h14v2H5z"/>
                                        </svg>
                                    </span>
                                    <span class="support-metric-count"
                                        :class="sortBy === 'count' ? 'is-active' : 'is-inactive'"
                                        x-text="row.count + ' ' + (row.count === 1 ? 'Call' : 'Calls')"></span>
                                    <span class="support-metric-duration"
                                        :class="sortBy === 'duration' ? 'is-active' : 'is-inactive'"
                                        x-text="row.duration_formatted"></span>
                                    <svg class="support-expand-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 12 15 18 9"/>
                                    </svg>
                                </div>

                                <div class="support-rank-chart" x-show="expanded[row.name]" x-collapse @click.stop="openFullChart(row)" style="cursor: pointer;" title="Click to open full graph">
                                    <template x-if="row.series && row.series.length > 0">
                                        <div>
                                            <div class="chart-meta">
                                                <span x-text="row.granularity === 'hourly' ? 'Hourly call distribution' : 'Daily call distribution'"></span>
                                                <template x-if="peakInfo(row.series)">
                                                    <span>Peak: <strong x-text="peakInfo(row.series).count"></strong>
                                                        <span x-text="row.granularity === 'hourly' ? 'at' : 'on'"></span>
                                                        <strong x-text="peakInfo(row.series).label"></strong></span>
                                                </template>
                                            </div>
                                            <svg viewBox="0 0 300 60" preserveAspectRatio="none" style="width: 100%; height: 56px; display: block;">
                                                <line x1="0" y1="55" x2="300" y2="55" stroke="#e5e7eb" stroke-width="0.5"/>
                                                <path :d="chartArea(row.series)" fill="rgba(99,102,241,0.12)" stroke="none"/>
                                                <polyline :points="chartPath(row.series)" fill="none" stroke="#4338ca" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                                                <template x-for="(p, idx) in chartPoints(row.series)" :key="idx">
                                                    <circle :cx="p.x" :cy="p.y" :r="peakInfo(row.series) && p.date === peakInfo(row.series).date ? 3.2 : 2"
                                                        :fill="peakInfo(row.series) && p.date === peakInfo(row.series).date ? '#f59e0b' : '#4338ca'"
                                                        :stroke="peakInfo(row.series) && p.date === peakInfo(row.series).date ? '#fff' : 'none'"
                                                        stroke-width="1.5"
                                                        vector-effect="non-scaling-stroke">
                                                        <title x-text="p.label + ': ' + p.count + (p.count === 1 ? ' call' : ' calls')"></title>
                                                    </circle>
                                                </template>
                                            </svg>
                                            <div class="chart-labels">
                                                <span x-text="row.series[0].label"></span>
                                                <span x-show="row.series.length > 2" x-text="row.series[Math.floor(row.series.length / 2)].label"></span>
                                                <span x-text="row.series[row.series.length - 1].label"></span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!row.series || row.series.length === 0">
                                        <div class="support-rank-chart-empty">No date range available for chart.</div>
                                    </template>
                                </div>

                                <span class="support-metric-bar"
                                    :style="`width: ${(sortBy === 'duration' ? row.duration / maxDuration : row.count / maxCount) * 100}%`"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            </div>{{-- /slide-over-overlay --}}

            {{-- Full chart modal --}}
            <div
                x-show="focusedRow"
                class="full-chart-overlay"
                @click.self="focusedRow = null; hoverPoint = null; $event.stopPropagation()"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                style="display: none;"
            >
                <div class="full-chart-modal" @click.stop>
                    <template x-if="focusedRow">
                        <div style="display:contents;">
                            <div class="full-chart-header">
                                <div>
                                    <h3 x-text="focusedRow.name + (focusedRow.granularity === 'hourly' ? ' — Hourly Call Distribution' : ' — Daily Call Distribution')"></h3>
                                    <p x-text="'Hover the chart to see exact values for each ' + (focusedRow.granularity === 'hourly' ? 'hour.' : 'day.')"></p>
                                </div>
                                <button type="button" class="full-chart-close" @click="focusedRow = null; hoverPoint = null">&times;</button>
                            </div>
                            <template x-if="!focusedRow.series || focusedRow.series.length === 0 || sumCalls(focusedRow.series) === 0">
                                <div class="full-chart-body">
                                    <div class="full-chart-empty">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 3v18h18"/>
                                            <path d="M7 14l3-3 3 3 4-5"/>
                                            <circle cx="7" cy="14" r="0.5" fill="currentColor"/>
                                            <circle cx="10" cy="11" r="0.5" fill="currentColor"/>
                                            <circle cx="13" cy="14" r="0.5" fill="currentColor"/>
                                            <circle cx="17" cy="9" r="0.5" fill="currentColor"/>
                                        </svg>
                                        <p class="full-chart-empty-title">No timeline data</p>
                                        <p class="full-chart-empty-sub" x-text="'No calls were recorded for this ' + (focusedRow.granularity === 'hourly' ? 'period' : 'date range') + '.'"></p>
                                    </div>
                                </div>
                            </template>

                            <div class="full-chart-body" x-show="focusedRow.series && focusedRow.series.length > 0 && sumCalls(focusedRow.series) > 0">
                                <div class="full-chart-stats">
                                    <div class="full-chart-stat">
                                        <div class="full-chart-stat-label">Total Calls</div>
                                        <div class="full-chart-stat-value" x-text="sumCalls(focusedRow.series)"></div>
                                    </div>
                                    <div class="full-chart-stat">
                                        <div class="full-chart-stat-label" x-text="focusedRow.granularity === 'hourly' ? 'Peak Hour' : 'Peak Day'"></div>
                                        <div class="full-chart-stat-value">
                                            <template x-if="peakInfo(focusedRow.series)">
                                                <span><span x-text="peakInfo(focusedRow.series).count"></span>
                                                    <span x-text="focusedRow.granularity === 'hourly' ? 'at' : 'on'"></span>
                                                    <span x-text="peakInfo(focusedRow.series).label"></span></span>
                                            </template>
                                            <template x-if="!peakInfo(focusedRow.series)">
                                                <span>—</span>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="full-chart-stat">
                                        <div class="full-chart-stat-label" x-text="focusedRow.granularity === 'hourly' ? 'Hourly Average' : 'Daily Average'"></div>
                                        <div class="full-chart-stat-value">
                                            <span x-text="avgPerBucket(focusedRow.series)"></span><span style="color:#9ca3af; font-weight:500; font-size: 11px;" x-text="' calls / ' + (focusedRow.granularity === 'hourly' ? 'hour' : 'day')"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="full-chart-svg-wrap">
                                    <svg viewBox="0 0 600 240"
                                        preserveAspectRatio="xMidYMid meet"
                                        style="width: 100%; height: auto; display: block; overflow: visible;">
                                        {{-- Y-axis grid + tick stubs + numeric labels (innerHTML for reliable SVG-namespace rendering) --}}
                                        <g x-html="yAxisHtml(focusedRow.series)"></g>

                                        {{-- Y-axis line --}}
                                        <line x1="40" y1="16" x2="40" y2="184" stroke="#6b7280" stroke-width="1.2"/>

                                        {{-- X-axis line --}}
                                        <line x1="40" y1="184" x2="584" y2="184" stroke="#6b7280" stroke-width="1.2"/>

                                        {{-- Area fill --}}
                                        <path :d="fullChartArea(focusedRow.series)" fill="rgba(99,102,241,0.14)"/>

                                        {{-- Line --}}
                                        <polyline :points="fullChartPath(focusedRow.series)"
                                            fill="none" stroke="#4338ca" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            vector-effect="non-scaling-stroke"/>

                                        {{-- Dashed vertical line at hover --}}
                                        <line x-show="hoverPoint"
                                            :x1="hoverPoint ? hoverPoint.x : 0" y1="16"
                                            :x2="hoverPoint ? hoverPoint.x : 0" y2="184"
                                            stroke="#4338ca" stroke-width="1" stroke-dasharray="3 3" opacity="0.5"
                                            vector-effect="non-scaling-stroke"/>

                                        {{-- Static data points (peak highlighted gold) --}}
                                        <g x-html="chartPointsHtml(focusedRow.series)"></g>

                                        {{-- Single mousemove-tracking hover rect across the plot area --}}
                                        <rect x="40" y="16" width="544" height="168"
                                            fill="rgba(255,255,255,0)"
                                            @mousemove="hoverFromMouse($event, focusedRow.series)"
                                            @mouseleave="hoverPoint = null"
                                            style="cursor: crosshair; pointer-events: all;"/>

                                        {{-- Hover highlight circle (overlaid above hit rect) --}}
                                        <circle x-show="hoverPoint"
                                            :cx="hoverPoint ? hoverPoint.x : 0"
                                            :cy="hoverPoint ? hoverPoint.y : 0"
                                            r="5" fill="#f59e0b" stroke="#fff" stroke-width="1.8"
                                            style="pointer-events: none;"/>

                                        {{-- X-axis tick stubs + labels (innerHTML) --}}
                                        <g x-html="xAxisHtml(focusedRow.series)"></g>

                                        {{-- Static axis titles --}}
                                        <text x="312" y="228" text-anchor="middle" fill="#1f2937" font-size="12" font-weight="700"
                                            x-text="focusedRow.granularity === 'hourly' ? 'Hour of day' : 'Date'"></text>
                                        <text x="14" y="100" text-anchor="middle" fill="#1f2937" font-size="12" font-weight="700"
                                            transform="rotate(-90, 14, 100)">Calls</text>

                                        {{-- Tooltip (auto-flips below if too high) --}}
                                        <g x-show="hoverPoint" :transform="hoverPoint ? 'translate(' + hoverPoint.x + ',' + hoverPoint.y + ')' : ''" style="pointer-events: none;">
                                            <g :transform="hoverPoint && hoverPoint.y < 60 ? 'translate(0, 14)' : 'translate(0, -14)'">
                                                <rect x="-54" :y="hoverPoint && hoverPoint.y < 60 ? 0 : -36" width="108" height="36" fill="#111827" rx="6"/>
                                                <polygon :points="hoverPoint && hoverPoint.y < 60 ? '-5,0 5,0 0,-5' : '-5,0 5,0 0,5'" fill="#111827"/>
                                                <text x="0" :y="hoverPoint && hoverPoint.y < 60 ? 15 : -22" text-anchor="middle" fill="#fff" font-size="11" font-weight="700" x-text="hoverPoint ? hoverPoint.label : ''"></text>
                                                <text x="0" :y="hoverPoint && hoverPoint.y < 60 ? 29 : -8" text-anchor="middle" fill="#fff" font-size="11" x-text="hoverPoint ? (hoverPoint.count + (hoverPoint.count === 1 ? ' call' : ' calls')) : ''"></text>
                                            </g>
                                        </g>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>{{-- /x-data wrapper --}}
    </template>

    {{-- Slide Over Panel for Staff Stats - ALWAYS visible --}}
    <template x-teleport="body">
        <div
            x-data="{ open: @entangle('showStaffStats') }"
            x-show="open"
            @keydown.window.escape="open = false"
            class="slide-over-overlay"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            style="display: none;"
        >
            <div
                class="slide-over-modal"
                @click.away="open = false"
            >
                <!-- Header -->
                <div class="slide-over-header">
                    <h2 class="text-lg font-bold text-gray-800">{{ $slideOverTitle }}</h2>
                    <button @click="open = false" class="p-1 text-2xl leading-none text-gray-500 hover:text-gray-700">&times;</button>
                </div>

                <!-- Scrollable content -->
                <div class="slide-over-content">
                    @if($type === 'duration')
                        <!-- Total count - similar to project-priority style -->
                        <div class="slide-over-total slide-over-total-medium">
                            <span class="slide-over-total-label">Total Call Time:</span>
                            <span class="slide-over-total-value">
                                @php
                                    $totalHours = 0;
                                    $totalMinutes = 0;

                                    foreach($staffStats as $staff) {
                                        if (isset($staff['total_duration'])) {
                                            $hours = floor($staff['total_duration'] / 3600);
                                            $minutes = floor(($staff['total_duration'] % 3600) / 60);
                                            $totalHours += $hours;
                                            $totalMinutes += $minutes;
                                        }
                                    }

                                    // Convert excess minutes to hours
                                    $totalHours += floor($totalMinutes / 60);
                                    $totalMinutes = $totalMinutes % 60;
                                @endphp
                                {{ $totalHours }}h {{ $totalMinutes }}m
                            </span>
                        </div>

                        <!-- Staff list with expandable date lists -->
                        <div class="space-y-0">
                            @foreach($staffStats as $staff)
                                @if(isset($staff['name']))
                                    <!-- Staff item -->
                                    <div class="implementer-item" wire:click="toggleStaff('{{ $staff['name'] }}')">
                                        <div class="implementer-name {{ in_array($staff['name'], $expandedStaff ?? []) ? 'expanded' : '' }}">
                                            {{ $staff['name'] }}

                                            <!-- Chevron icon -->
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="implementer-count">
                                            {{ isset($staff['formatted_time']) ? $staff['formatted_time'] : '0h 0m' }}
                                        </div>
                                    </div>

                                    <!-- Date list - expandable -->
                                    <div class="date-list {{ in_array($staff['name'], $expandedStaff ?? []) ? 'expanded' : '' }}">
                                        @if(isset($staffDateTimes[$staff['name']]))
                                            <table class="w-full">
                                                <tbody>
                                                    @foreach($staffDateTimes[$staff['name']] as $dateData)
                                                        <tr class="date-item">
                                                            <td class="py-1" style= "width: 25%; text-align: right;">{{ $dateData['display_date'] }}</td>
                                                            <td class="py-1" style= "width: 10%">
                                                                @php
                                                                    // Convert the display date to a DateTime object and get day name
                                                                    $date = \DateTime::createFromFormat('j M Y', $dateData['display_date']);
                                                                    $dayName = $date ? $date->format('D') : '';
                                                                @endphp
                                                                <span class="text-gray-600">{{ $dayName }}</span>
                                                            </td>
                                                            <td style= "width: 45%"></td>
                                                            <td class="py-1 text-right" style= "width: 20%">
                                                                <span class="px-2 py-1 text-xs staff-number-time">{{ $dateData['formatted_time'] }}</span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <!-- Regular non-hierarchical view for other stats -->
                        @foreach ($staffStats as $staff)
                            <div class="px-3 py-2 mb-1 staff-stats-card">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm staff-name">{{ $staff['name'] }}</div>

                                    <!-- Show the right number based on type -->
                                    @if($type === 'completed')
                                        <div class="w-5 h-5 text-xs group-badge">{{ $staff['completed_calls'] }}</div>
                                    @elseif($type === 'pending')
                                        <div class="w-5 h-5 text-xs group-badge">{{ $staff['pending_calls'] }}</div>
                                    @else
                                        <div class="w-5 h-5 text-xs group-badge">{{ $staff['total_calls'] }}</div>
                                    @endif
                                </div>

                                <div class="mt-0.5 text-xs text-gray-500">
                                    Extension: {{ $staff['extension'] }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </template>

    {{-- TABLE - ALWAYS visible --}}
    {{ $this->table }}
</x-filament::page>
