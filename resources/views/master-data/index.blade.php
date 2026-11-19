@extends('layouts.app')

@section('title', 'Master Barang — ' . config('app.name', 'SIGMA'))

@push('styles')
    <style>
        .master-barang-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .master-barang-form-grid .form-group {
            min-width: 0;
            margin-bottom: 0;
        }

        .master-barang-form-grid .form-group-full {
            grid-column: 1 / -1;
        }

        .master-barang-header-actions,
        .master-barang-actions,
        .master-barang-modal-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .master-data-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            border-bottom: 2px solid var(--surface-border);
            margin-bottom: 20px;
        }

        .master-data-tabs .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 40px;
            flex: 0 0 auto;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .catalog-filter-card .form-control:focus-visible,
        .catalog-row-actions .btn:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        .catalog-page-header {
            align-items: stretch;
            padding: 16px 18px;
            margin-bottom: 16px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
            box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
        }

        .catalog-page-heading {
            display: flex;
            flex: 1 1 220px;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }

        .catalog-page-header .page-title {
            font-size: 1.35rem;
        }

        .catalog-page-header .page-subtitle {
            max-width: 520px;
        }

        .catalog-header-actions,
        .catalog-summary-cards,
        .catalog-filter-primary,
        .catalog-filter-secondary,
        .catalog-filter-actions,
        .catalog-quick-filters,
        .catalog-item-heading,
        .catalog-warehouse-list,
        .catalog-row-actions,
        .catalog-results-footer,
        .catalog-pagination-size {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .catalog-header-actions {
            flex: 0 1 auto;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .catalog-summary-cards {
            flex-wrap: wrap;
        }

        .catalog-summary-card {
            display: flex;
            min-width: 138px;
            flex-direction: column;
            gap: 2px;
            padding: 9px 12px;
            border-radius: var(--radius);
            background: rgba(20, 92, 84, .07);
        }

        .catalog-summary-label {
            color: var(--text-muted);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .045em;
            text-transform: uppercase;
        }

        .catalog-summary-value {
            color: var(--text-primary);
            font-size: 1.1rem;
            font-weight: 800;
            line-height: 1.3;
        }

        .catalog-summary-note {
            color: var(--text-muted);
            font-size: .8rem;
        }

        .catalog-create-button {
            min-height: 44px;
        }

        .catalog-create-toolbar {
            display: flex;
            justify-content: flex-end;
            margin-top: -8px;
            margin-bottom: 16px;
        }

        .master-import-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .master-import-button {
            min-height: 44px;
        }

        .master-import-backdrop {
            position: fixed;
            z-index: 1205;
            inset: 0;
            display: grid;
            place-items: center;
            padding: 20px;
            background: rgba(20, 28, 36, .48);
        }

        .master-import-dialog {
            display: flex;
            width: min(980px, 100%);
            max-height: min(880px, calc(100dvh - 40px));
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
            box-shadow: 0 18px 52px rgba(20, 28, 36, .2);
        }

        .master-import-dialog-header,
        .master-import-dialog-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--surface-border);
        }

        .master-import-dialog-footer {
            justify-content: flex-end;
            border-top: 1px solid var(--surface-border);
            border-bottom: 0;
        }

        .master-import-dialog-body {
            display: grid;
            gap: 16px;
            overflow-y: auto;
            padding: 20px;
        }

        .master-import-stepper {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .master-import-step {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 9px;
            padding: 10px 12px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: .82rem;
            font-weight: 650;
        }

        .master-import-step-number {
            display: grid;
            width: 26px;
            height: 26px;
            flex: 0 0 auto;
            place-items: center;
            border: 1px solid var(--surface-border);
            border-radius: 50%;
            background: var(--surface-card);
            color: var(--text-muted);
            font-size: .75rem;
            font-variant-numeric: tabular-nums;
        }

        .master-import-step.is-active {
            border-color: var(--brand-primary);
            background: rgba(20, 92, 84, .06);
            color: var(--brand-primary);
        }

        .master-import-step.is-active .master-import-step-number,
        .master-import-step.is-complete .master-import-step-number {
            border-color: var(--brand-primary);
            background: var(--brand-primary);
            color: #fff;
        }

        .master-import-step.is-complete {
            color: var(--text-primary);
        }

        .master-import-callout {
            padding: 12px 14px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
        }

        .master-import-callout strong {
            color: var(--text-primary);
        }

        .master-import-callout p {
            margin: 4px 0 0;
        }

        .master-import-dialog-title {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.05rem;
            font-weight: 750;
        }

        .master-import-dialog-note,
        .master-import-feedback {
            color: var(--text-muted);
            font-size: .84rem;
            line-height: 1.5;
        }

        .master-import-mapping {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .master-import-summary {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
        }

        .master-import-summary-results {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }

        .master-import-summary-item {
            min-width: 0;
            padding: 11px 12px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
        }

        .master-import-summary-item span {
            display: block;
            color: var(--text-muted);
            font-size: .72rem;
        }

        .master-import-summary-item strong {
            display: block;
            margin-top: 3px;
            color: var(--text-primary);
            font-size: 1.1rem;
            font-variant-numeric: tabular-nums;
        }

        .master-import-preview-table {
            max-height: 360px;
            overflow: auto;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
        }

        .master-import-preview-table .data-table {
            min-width: 760px;
        }

        .master-import-feedback-error {
            padding: 10px 12px;
            border: 1px solid rgba(193, 68, 60, .25);
            border-radius: var(--radius);
            background: rgba(193, 68, 60, .07);
            color: var(--status-critical);
        }

        .master-import-status {
            display: inline-flex;
            min-height: 28px;
            align-items: center;
            padding: 3px 8px;
            border-radius: 999px;
            border: 1px solid transparent;
            background: rgba(20, 92, 84, .09);
            color: var(--brand-primary);
            font-size: .75rem;
            font-weight: 700;
        }

        .master-import-status.is-duplicate {
            border-color: var(--surface-border);
            background: var(--surface-base);
            color: var(--text-primary);
        }

        .master-import-status.is-error {
            border-color: rgba(193, 68, 60, .25);
            background: rgba(193, 68, 60, .08);
            color: var(--status-critical);
        }

        .master-import-progress {
            height: 10px;
            overflow: hidden;
            border-radius: 999px;
            background: var(--surface-base);
        }

        .master-import-progress-value {
            height: 100%;
            border-radius: inherit;
            background: var(--brand-primary);
            transition: width 200ms ease;
        }

        .master-import-progress-header {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 12px 16px;
            margin-bottom: 18px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
        }

        .master-import-progress-step {
            display: flex;
            min-width: max-content;
            flex: 1 0 auto;
            align-items: center;
            gap: 8px;
        }

        .master-import-phase-dot {
            display: grid;
            width: 28px;
            height: 28px;
            flex: 0 0 auto;
            place-items: center;
            border: 2px solid var(--surface-border);
            border-radius: 50%;
            color: var(--text-muted);
            font-size: .75rem;
            font-weight: 800;
        }

        .master-import-phase-dot svg {
            width: 14px;
            height: 14px;
        }

        .master-import-phase-dot.is-active {
            border-color: var(--brand-accent);
            background: var(--brand-accent);
            color: #fff;
        }

        .master-import-phase-dot.is-done {
            border-color: var(--brand-primary);
            background: var(--brand-primary);
            color: #fff;
        }

        .master-import-phase-label {
            color: var(--text-muted);
            font-size: .8rem;
            white-space: nowrap;
        }

        .master-import-phase-label.is-active {
            color: var(--brand-accent);
            font-weight: 750;
        }

        .master-import-phase-label.is-done {
            color: var(--brand-primary);
        }

        .master-import-phase-connector {
            height: 2px;
            min-width: 18px;
            flex: 1;
            background: var(--surface-border);
        }

        .master-import-phase-connector.is-done {
            background: var(--brand-primary);
        }

        .master-import-stage {
            padding: 14px;
        }

        .master-import-stage .card-header {
            margin-bottom: 10px;
            padding-bottom: 10px;
        }

        .master-import-help {
            margin: 4px 0 0;
            color: var(--text-muted);
            font-size: .82rem;
            line-height: 1.5;
        }

        .master-import-file-input {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            clip-path: inset(50%);
        }

        .master-import-file-input:focus-visible+.master-import-dropzone {
            outline: 3px solid var(--brand-accent);
            outline-offset: 3px;
        }

        .master-import-dropzone {
            display: grid;
            width: 100%;
            min-height: 104px;
            place-items: center;
            margin-top: 8px;
            padding: 12px 16px;
            border: 1px dashed var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--text-primary);
            cursor: pointer;
            text-align: center;
            transition: border-color 160ms ease, background-color 160ms ease;
        }

        .master-import-dropzone:hover,
        .master-import-dropzone.is-dragging {
            border-color: var(--brand-accent);
            background: rgba(201, 122, 43, .05);
        }

        .master-import-dropzone:disabled {
            cursor: wait;
        }

        .master-import-dropzone.has-file {
            min-height: 68px;
            place-items: center start;
            padding: 8px 12px;
            border-style: solid;
            text-align: left;
        }

        .master-import-dropzone.has-file:hover {
            background: rgba(20, 92, 84, .04);
        }

        .master-import-dropzone-content {
            display: grid;
            justify-items: center;
            gap: 4px;
        }

        .master-import-dropzone-content svg {
            width: 28px;
            height: 28px;
            color: var(--brand-primary);
        }

        .master-import-dropzone-content strong {
            font-size: .84rem;
        }

        .master-import-dropzone-content span,
        .master-import-file-selected small {
            color: var(--text-muted);
            font-size: .8rem;
        }

        .master-import-file-selected {
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
        }

        .master-import-file-selected>svg {
            width: 36px;
            height: 36px;
            flex: 0 0 auto;
            color: var(--brand-primary);
        }

        .master-import-file-selected>span:nth-child(2) {
            display: grid;
            gap: 3px;
            overflow-wrap: anywhere;
        }

        .master-import-ready-pill,
        .master-import-count-pill {
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
            font-size: .72rem;
            font-weight: 750;
            white-space: nowrap;
        }

        .master-import-info {
            margin-top: 8px;
            padding: 8px 10px;
            border: 1px solid rgba(20, 92, 84, .15);
            border-radius: var(--radius);
            background: rgba(20, 92, 84, .05);
            color: var(--text-muted);
            font-size: .8rem;
            line-height: 1.5;
        }

        .master-import-info strong {
            color: var(--brand-primary);
        }

        .master-import-info p {
            margin: 2px 0;
        }

        .master-import-mapping-feedback {
            margin-top: 10px;
            padding: 10px 12px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            font-size: .82rem;
            font-weight: 650;
            line-height: 1.5;
        }

        .master-import-mapping-feedback.is-pending {
            border-color: rgba(201, 122, 43, .3);
            background: rgba(201, 122, 43, .08);
            color: var(--brand-accent);
        }

        .master-import-mapping-feedback.is-ready {
            border-color: rgba(20, 92, 84, .2);
            background: rgba(20, 92, 84, .06);
            color: var(--brand-primary);
        }

        .master-import-stage-actions,
        .master-import-stage-actions-between {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 16px;
        }

        .master-import-stage-actions-between {
            justify-content: space-between;
        }

        .master-import-workspace {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            align-items: start;
            gap: 12px;
        }

        .master-import-config-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(280px, .8fr);
            align-items: start;
            gap: 12px;
        }

        .master-import-pagination-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .master-import-config-card {
            min-width: 0;
            padding: 14px;
        }

        .master-import-section-heading,
        .master-import-review-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .master-import-section-heading h2,
        .master-import-review-heading h2 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .master-import-section-heading p,
        .master-import-review-heading p {
            margin: 4px 0 0;
            color: var(--text-muted);
            font-size: .8rem;
        }

        .master-import-sheet-list,
        .master-import-filter-list {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .master-import-sheet,
        .master-import-filter-list button {
            min-height: 44px;
            padding: 7px 12px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--text-muted);
            font: inherit;
            font-size: .78rem;
            white-space: nowrap;
            cursor: pointer;
        }

        .master-import-sheet.is-active,
        .master-import-filter-list button.is-active {
            border-color: var(--brand-primary);
            background: rgba(20, 92, 84, .08);
            color: var(--brand-primary);
            font-weight: 750;
        }

        .master-import-header-preview,
        .master-import-review-table {
            width: 100%;
            max-width: 100%;
            overflow: auto;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
        }

        .master-import-header-preview {
            max-height: 280px;
            margin-bottom: 8px;
        }

        .master-import-header-preview table {
            width: max-content;
            min-width: 100%;
            border-collapse: collapse;
            font-size: .7rem;
        }

        .master-import-header-preview th {
            position: sticky;
            top: 0;
            z-index: 2;
            padding: 4px;
            border-bottom: 1px solid var(--surface-border);
            background: var(--surface-card);
            color: var(--text-primary);
            text-align: left;
        }

        .master-import-header-preview td {
            max-width: 190px;
            padding: 5px 8px;
            border-bottom: 1px solid var(--surface-border);
            color: var(--text-primary);
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        .master-import-column-button {
            min-height: 44px;
            max-width: 220px;
            padding: 6px 9px;
            border: 1px solid var(--surface-border);
            border-radius: 6px;
            background: var(--surface-base);
            color: var(--text-primary);
            font: inherit;
            font-size: .72rem;
            cursor: pointer;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        .master-import-column-button:hover,
        .master-import-column-button.is-selected {
            border-color: var(--brand-primary);
            background: rgba(20, 92, 84, .09);
            color: var(--brand-primary);
        }

        .master-import-column-picker {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(150px, .8fr);
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .master-import-column-picker .form-label {
            margin: 0;
        }

        .master-import-column-picker .form-control {
            min-height: 40px;
            height: 40px;
        }

        .master-import-row-number {
            position: sticky;
            left: 0;
            z-index: 1;
            min-width: 54px;
            background: var(--surface-card);
            text-align: center;
        }

        .master-import-row-number button {
            min-width: 44px;
            min-height: 44px;
            padding: 4px 8px;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: var(--text-muted);
            font: inherit;
            font-size: .75rem;
            cursor: pointer;
        }

        .master-import-row-number button.is-selected {
            background: var(--brand-primary);
            color: #fff;
            font-weight: 800;
        }

        .master-import-header-preview td.is-header-cell {
            background: rgba(20, 92, 84, .09);
            color: var(--brand-primary);
            font-weight: 750;
        }

        .master-import-mapping-list {
            display: grid;
            gap: 8px;
        }

        .master-import-mapping-row.has-error .form-control {
            border-color: var(--status-critical);
            box-shadow: 0 0 0 2px rgba(190, 54, 48, .12);
        }

        .master-import-mapping-row {
            display: grid;
            grid-template-columns: minmax(110px, .8fr) minmax(0, 1.2fr);
            align-items: center;
            gap: 8px;
        }

        .master-import-mapping-row label {
            color: var(--text-primary);
            font-size: .76rem;
            font-weight: 650;
        }

        .master-import-mapping-row .form-control {
            min-height: 40px;
            height: 40px;
            padding: 5px 9px;
            font-size: .8rem;
        }

        .master-import-config-card #master-import-header-row {
            width: 112px;
            min-height: 36px;
            height: 36px;
            padding: 5px 9px;
            font-size: .82rem;
        }

        .master-import-required,
        .master-import-error {
            color: var(--status-critical);
        }

        .master-import-error {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            margin: 10px 0 0;
            padding: 9px 11px;
            border: 1px solid rgba(190, 54, 48, .28);
            border-radius: 6px;
            background: rgba(190, 54, 48, .06);
            font-size: .82rem;
        }

        .master-import-error-icon {
            flex: 0 0 auto;
            width: 17px;
            height: 17px;
            margin-top: 1px;
        }

        .master-import-review-heading {
            align-items: center;
            margin: 0 0 14px;
        }

        .master-import-review {
            min-width: 0;
        }

        .master-import-review-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            align-items: start;
            gap: 12px;
        }

        .master-import-review-table-card,
        .master-import-confirm-card {
            min-width: 0;
            padding: 14px;
        }

        .master-import-review-table {
            max-height: 480px;
        }

        .master-import-review-table .data-table {
            min-width: 760px;
        }

        .master-import-filter-list button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .master-import-filter-list button span {
            font-variant-numeric: tabular-nums;
        }

        .master-import-status-pill {
            display: inline-flex;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(20, 92, 84, .09);
            color: var(--brand-primary);
            font-size: .75rem;
            font-weight: 750;
            white-space: nowrap;
        }

        .master-import-status-pill.is-duplicate {
            background: rgba(201, 122, 43, .12);
            color: var(--brand-accent);
        }

        .master-import-status-pill.is-error {
            background: rgba(193, 68, 60, .1);
            color: var(--status-critical);
        }

        .master-import-issues {
            margin: 5px 0 0;
            padding-left: 16px;
            color: var(--status-critical);
            font-size: .72rem;
        }

        .master-import-confirm-card h3 {
            margin: 0 0 12px;
            font-size: .95rem;
        }

        .master-import-stat-list {
            display: grid;
            gap: 8px;
        }

        .master-import-stat-list>div {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--surface-border);
            color: var(--text-muted);
            font-size: .78rem;
        }

        .master-import-stat-list strong {
            color: var(--text-primary);
            font-variant-numeric: tabular-nums;
        }

        .master-import-confirm-button {
            width: 100%;
            min-height: 44px;
            margin-top: 14px;
        }

        .master-import-waiting,
        .master-import-result-card {
            display: grid;
            justify-items: center;
            gap: 10px;
            padding: 40px 24px;
            text-align: center;
        }

        .master-import-waiting h2,
        .master-import-result-card h2 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.2rem;
        }

        .master-import-waiting p,
        .master-import-result-card p {
            max-width: 640px;
            margin: 0;
            color: var(--text-muted);
            font-size: .85rem;
            line-height: 1.5;
        }

        .master-import-spinner {
            width: 38px;
            height: 38px;
            border: 4px solid var(--surface-border);
            border-top-color: var(--brand-accent);
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        .master-import-progress-track {
            width: min(520px, 100%);
            height: 10px;
            overflow: hidden;
            border-radius: 999px;
            background: var(--surface-base);
        }

        .master-import-progress-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: var(--brand-primary);
            transition: width 200ms ease;
        }

        .master-import-result-icon {
            display: grid;
            width: 64px;
            height: 64px;
            place-items: center;
            border-radius: 50%;
            font-size: 2rem;
            font-weight: 800;
        }

        .master-import-result-icon svg {
            width: 32px;
            height: 32px;
        }

        .master-import-result-icon.is-success {
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
        }

        .master-import-result-icon.is-error {
            background: rgba(193, 68, 60, .1);
            color: var(--status-critical);
        }

        .master-import-result-stats {
            display: grid;
            width: min(780px, 100%);
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin: 12px 0;
        }

        .master-import-result-stats>div {
            display: grid;
            gap: 4px;
            padding: 12px 8px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
        }

        .master-import-result-stats strong {
            color: var(--text-primary);
            font-size: 1.25rem;
            font-variant-numeric: tabular-nums;
        }

        .master-import-result-stats span {
            color: var(--text-muted);
            font-size: .72rem;
        }

        .catalog-filter-card {
            display: grid;
            grid-template-columns: minmax(160px, 1.6fr) repeat(2, minmax(105px, 1fr)) minmax(130px, 1.3fr) minmax(100px, 1fr) auto;
            align-items: end;
            gap: 12px;
            padding: 14px;
            margin-bottom: 16px;
            border-color: var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
            box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
        }

        .catalog-filter-primary {
            display: contents;
        }

        .catalog-filter-secondary {
            display: contents;
        }

        .catalog-filter-card>.catalog-quick-filters {
            grid-column: 1 / -1;
        }

        .catalog-filter-card .form-group {
            min-width: 0;
            margin: 0;
        }

        .catalog-filter-card .form-label {
            color: var(--text-muted);
            font-size: .75rem;
            font-weight: 750;
        }

        .catalog-search-field {
            position: relative;
        }

        .catalog-search-field .form-control {
            padding-left: 38px;
        }

        .catalog-search-icon {
            position: absolute;
            top: 50%;
            left: 12px;
            color: var(--text-muted);
            pointer-events: none;
            transform: translateY(-50%);
        }

        .catalog-warehouse-filter {
            position: relative;
        }

        .catalog-warehouse-control {
            display: flex;
            min-height: 42px;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
            padding: 4px 8px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: #fff;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .catalog-warehouse-control:focus-within {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(20, 92, 84, .12);
        }

        .catalog-warehouse-chip {
            display: inline-flex;
            max-width: 100%;
            min-height: 30px;
            align-items: center;
            gap: 4px;
            padding: 2px 4px 2px 8px;
            border: 1px solid rgba(20, 92, 84, .18);
            border-radius: 5px;
            background: rgba(20, 92, 84, .07);
            color: var(--brand-primary);
            font-size: .76rem;
            font-weight: 600;
        }

        .catalog-warehouse-chip-label {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .catalog-warehouse-chip-remove {
            display: inline-grid;
            width: 28px;
            min-width: 28px;
            height: 28px;
            place-items: center;
            border: 0;
            border-radius: 4px;
            background: transparent;
            color: inherit;
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
        }

        .catalog-warehouse-chip-remove:hover,
        .catalog-warehouse-chip-remove:focus-visible {
            background: rgba(20, 92, 84, .12);
        }

        .catalog-warehouse-picker-trigger {
            min-height: 32px;
            padding: 4px 6px;
            border: 0;
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            font: inherit;
            font-size: .78rem;
            text-align: left;
        }

        .catalog-warehouse-picker-trigger:hover,
        .catalog-warehouse-picker-trigger:focus-visible {
            color: var(--brand-primary);
        }

        .catalog-warehouse-picker {
            position: absolute;
            z-index: 20;
            top: calc(100% + 5px);
            right: 0;
            left: 0;
            display: grid;
            gap: 7px;
            padding: 9px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            box-shadow: 0 8px 20px rgba(0, 0, 0, .12);
        }

        .catalog-warehouse-picker-search {
            min-height: 38px;
            padding: 7px 10px;
            font-size: .82rem;
        }

        .catalog-warehouse-picker-list {
            display: grid;
            max-height: 220px;
            gap: 2px;
            overflow-y: auto;
        }

        .catalog-warehouse-picker-option {
            display: flex;
            min-height: 40px;
            align-items: center;
            gap: 9px;
            padding: 6px 8px;
            border-radius: 5px;
            color: var(--text-primary);
            cursor: pointer;
            font-size: .82rem;
        }

        .catalog-warehouse-picker-option:hover {
            background: var(--surface-base);
        }

        .catalog-warehouse-picker-option input {
            width: 16px;
            height: 16px;
            accent-color: var(--brand-primary);
        }

        .catalog-warehouse-picker-empty {
            padding: 10px 8px;
            color: var(--text-muted);
            font-size: .8rem;
        }

        .catalog-warehouse-picker-clear {
            min-height: 36px;
            border: 0;
            border-top: 1px solid var(--surface-border);
            background: transparent;
            color: var(--brand-primary);
            cursor: pointer;
            font-size: .8rem;
            font-weight: 700;
            text-align: left;
        }

        .catalog-filter-actions {
            justify-content: flex-end;
            min-height: 40px;
        }

        .catalog-filter-actions .btn {
            min-height: 44px;
        }

        .catalog-filter-reset {
            width: 44px;
            min-width: 44px;
            justify-content: center;
            padding: 0;
        }

        .catalog-quick-filters {
            flex-wrap: wrap;
            padding-top: 12px;
            border-top: 1px solid var(--surface-border);
        }

        .catalog-quick-label {
            color: var(--text-muted);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .catalog-quick-filter {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border: 1px solid var(--surface-border);
            border-radius: 6px;
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: .75rem;
            font-weight: 700;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease;
        }

        .catalog-quick-filter:hover,
        .catalog-quick-filter:focus-visible {
            border-color: var(--brand-primary);
            color: var(--brand-primary);
        }

        .catalog-quick-filter.is-active {
            border-color: var(--brand-primary);
            background: var(--brand-primary);
            color: #fff;
        }

        .catalog-results-card {
            overflow: hidden;
            padding: 0;
            border-color: var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
            box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
        }

        .catalog-table {
            min-width: 900px;
        }

        .catalog-table thead th {
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .045em;
            border-bottom: 1px solid var(--surface-border);
        }

        .catalog-table tbody td {
            padding-top: 12px;
            padding-bottom: 12px;
        }

        .catalog-table tbody tr {
            transition: background-color .15s ease;
        }

        .catalog-table tbody tr:hover {
            background: rgba(20, 92, 84, .035);
        }

        .catalog-code {
            display: inline-block;
            max-width: 105px;
            color: var(--brand-primary);
            font-size: .75rem;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .catalog-search-match {
            padding: 0 1px;
            border-radius: 2px;
            background: rgba(245, 190, 66, .38);
            color: inherit;
        }

        .catalog-item-name {
            display: block;
            font-weight: 700;
        }

        .catalog-item-inactive {
            display: inline-flex;
            margin-top: 4px;
        }

        .catalog-category {
            display: inline-block;
            max-width: 150px;
            padding: 4px 7px;
            border-radius: 4px;
            background: rgba(91, 107, 121, .1);
            color: var(--text-primary);
            font-size: .75rem;
            overflow-wrap: anywhere;
        }

        .catalog-warehouse-list {
            align-items: flex-start;
            flex-direction: column;
            gap: 4px;
        }

        .catalog-warehouse-empty {
            display: inline-block;
            max-width: 180px;
            padding: 4px 7px;
            border-radius: 4px;
            background: rgba(91, 107, 121, .1);
            color: var(--text-muted);
            font-size: .75rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .catalog-row-actions {
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .catalog-results-footer {
            justify-content: space-between;
            flex-wrap: wrap;
            padding: 12px 16px;
            background: rgba(20, 92, 84, .045);
        }

        .catalog-results-summary {
            color: var(--text-muted);
            font-size: .75rem;
        }

        .catalog-pagination-size {
            flex-wrap: wrap;
            color: var(--text-muted);
            font-size: .75rem;
        }

        .catalog-pagination-size label {
            font-weight: 700;
        }

        .catalog-pagination-size select {
            width: auto;
            min-height: 36px;
            padding: 5px 28px 5px 9px;
        }

        .satuan-page-header {
            margin-bottom: 18px;
        }

        .kategori-info-banner {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            padding: 11px 14px;
            margin-bottom: 12px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--text-primary);
            font-size: .82rem;
            line-height: 1.5;
        }

        .kategori-info-banner svg {
            flex: 0 0 auto;
            margin-top: 1px;
            color: var(--text-muted);
        }

        .kategori-toolbar {
            display: grid;
            grid-template-columns: minmax(240px, 1fr) minmax(180px, 240px);
            align-items: end;
            gap: 10px;
            padding: 12px;
            margin-bottom: 12px;
        }

        .kategori-toolbar .form-group {
            min-width: 0;
            margin: 0;
        }

        .kategori-table {
            min-width: 820px;
        }

        .kategori-table thead th {
            padding: 10px 12px;
            background: rgba(20, 92, 84, .06);
            font-size: .7rem;
            letter-spacing: .025em;
        }

        .kategori-table tbody td {
            padding: 11px 12px;
            font-size: .82rem;
        }

        .kategori-name {
            color: var(--text-primary);
            font-weight: 750;
        }

        .kategori-description {
            display: -webkit-box;
            max-width: 280px;
            overflow: hidden;
            color: var(--text-muted);
            line-height: 1.45;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .kategori-usage-count {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .kategori-status-inactive {
            background: rgba(91, 107, 121, .12);
            color: var(--status-neutral);
        }

        .kategori-action-menu {
            min-width: 176px;
        }

        .kategori-action-menu .kategori-deactivate-action {
            color: var(--status-critical);
        }

        .kategori-critical-button {
            border-color: var(--status-critical);
            background: var(--status-critical);
            color: #fff;
        }

        .kategori-results-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            padding: 11px 14px;
            background: rgba(20, 92, 84, .045);
            color: var(--text-muted);
            font-size: .78rem;
        }

        .kategori-empty-state {
            display: grid;
            justify-items: center;
            gap: 8px;
            padding: 42px 18px;
            color: var(--text-muted);
            text-align: center;
        }

        .kategori-empty-state svg {
            color: var(--text-muted);
        }

        .kategori-empty-state strong {
            color: var(--text-primary);
            font-size: .95rem;
        }

        .kategori-empty-state .btn {
            margin-top: 5px;
        }

        .kategori-skeleton-row td {
            padding-top: 14px;
            padding-bottom: 14px;
        }

        .kategori-skeleton-line {
            display: block;
            width: min(100%, 190px);
            height: 13px;
            border-radius: 5px;
            background: linear-gradient(90deg, var(--surface-base), rgba(91, 107, 121, .16), var(--surface-base));
            background-size: 200% 100%;
            animation: kategori-skeleton 1.3s ease-in-out infinite;
        }

        @keyframes kategori-skeleton {
            to {
                background-position: -200% 0;
            }
        }

        .kategori-modal-card {
            width: min(520px, calc(100vw - 32px));
            max-width: 520px;
        }

        .kategori-field-helper,
        .kategori-field-error {
            display: block;
            margin-top: 5px;
            font-size: .78rem;
            line-height: 1.45;
        }

        .kategori-field-helper {
            color: var(--text-muted);
        }

        .kategori-field-error {
            color: var(--status-critical);
        }

        .kategori-character-count {
            display: flex;
            justify-content: flex-end;
            margin-top: 4px;
            color: var(--text-muted);
            font-size: .74rem;
        }

        .kategori-status-toggle {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            gap: 9px;
            color: var(--text-primary);
            cursor: pointer;
            font-weight: 600;
        }

        .kategori-status-toggle input {
            width: 18px;
            height: 18px;
            accent-color: var(--brand-primary);
        }

        .kategori-change-note,
        .kategori-deactivate-note {
            padding: 10px 12px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: .75rem;
            line-height: 1.5;
        }

        .kategori-deactivate-note {
            border-color: rgba(184, 121, 26, .25);
            background: rgba(184, 121, 26, .09);
            color: var(--text-primary);
            font-size: .82rem;
        }

        .kategori-drawer-backdrop {
            position: fixed;
            z-index: 1100;
            inset: 0;
            display: flex;
            justify-content: flex-end;
            background: rgba(20, 28, 36, .42);
        }

        .kategori-drawer {
            display: flex;
            width: min(480px, 100vw);
            height: 100%;
            flex-direction: column;
            overflow: hidden;
            background: var(--surface-card);
            box-shadow: -12px 0 32px rgba(0, 0, 0, .16);
        }

        .kategori-drawer-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px 14px;
            border-bottom: 1px solid var(--surface-border);
        }

        .kategori-drawer-header h2 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.05rem;
        }

        .kategori-drawer-count {
            display: inline-flex;
            min-height: 26px;
            align-items: center;
            padding: 3px 9px;
            margin-top: 7px;
            border-radius: 999px;
            background: rgba(20, 92, 84, .08);
            color: var(--brand-primary);
            font-size: .75rem;
            font-weight: 750;
        }

        .kategori-drawer-close {
            display: inline-grid;
            width: 44px;
            min-width: 44px;
            height: 44px;
            place-items: center;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--text-primary);
            font-size: 1.35rem;
            text-decoration: none;
        }

        .kategori-drawer-search {
            padding: 12px 16px;
        }

        .kategori-drawer-search .form-group {
            margin: 0;
        }

        .kategori-drawer-content {
            flex: 1 1 auto;
            overflow: auto;
        }

        .kategori-drawer-table {
            min-width: 420px;
        }

        .kategori-drawer-table thead th,
        .kategori-drawer-table tbody td {
            padding: 9px 12px;
            font-size: .78rem;
        }

        .kategori-drawer-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            padding: 13px 16px;
            border-top: 1px solid var(--surface-border);
        }

        .kategori-drawer-link {
            color: var(--brand-primary);
            font-size: .82rem;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .master-satuan-panel {
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }

        .satuan-info-banner {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 10px 14px;
            margin-bottom: 12px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--text-primary);
            font-size: .82rem;
            line-height: 1.5;
        }

        .satuan-info-banner svg {
            flex: 0 0 auto;
            margin-top: 1px;
            color: var(--text-muted);
        }

        .satuan-toolbar {
            display: grid;
            grid-template-columns: minmax(240px, 1fr) minmax(180px, 240px) auto;
            align-items: end;
            gap: 8px;
            padding: 10px;
            margin-bottom: 12px;
        }

        .satuan-toolbar>.btn {
            min-height: 42px;
            padding: 8px 14px;
        }

        .satuan-toolbar .form-group {
            min-width: 0;
            margin: 0;
        }

        .satuan-table {
            min-width: 760px;
        }

        .satuan-table thead th {
            background: rgba(20, 92, 84, .06);
            padding: 9px 10px;
            font-size: .68rem;
            letter-spacing: .025em;
        }

        .satuan-table tbody td {
            padding: 9px 10px;
            font-size: .82rem;
        }

        .satuan-code {
            color: var(--text-primary);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .8rem;
            font-weight: 800;
        }

        .catalog-row-number {
            width: 52px;
            color: var(--text-muted);
            font-variant-numeric: tabular-nums;
            text-align: center;
        }

        .satuan-usage-count {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .satuan-status-inactive {
            background: rgba(91, 107, 121, .12);
            color: var(--status-neutral);
        }

        .satuan-actions {
            position: relative;
            display: flex;
            justify-content: flex-end;
        }

        .satuan-actions>summary {
            display: inline-flex;
            min-width: 76px;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 10px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--text-primary);
            cursor: pointer;
            font-size: .82rem;
            font-weight: 700;
            list-style: none;
        }

        .satuan-actions>summary::-webkit-details-marker {
            display: none;
        }

        .satuan-actions>summary:focus-visible,
        .satuan-action-menu button:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        .satuan-action-menu {
            position: absolute;
            z-index: 5;
            top: calc(100% + 4px);
            right: 0;
            display: grid;
            min-width: 160px;
            padding: 5px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            box-shadow: 0 8px 20px rgba(0, 0, 0, .12);
        }

        .satuan-action-menu button {
            min-height: 44px;
            padding: 8px 10px;
            border: 0;
            border-radius: 5px;
            background: transparent;
            color: var(--text-primary);
            cursor: pointer;
            text-align: left;
        }

        .satuan-action-menu button:hover {
            background: var(--surface-base);
        }

        .satuan-action-menu .satuan-deactivate-action {
            color: var(--status-critical);
        }

        .satuan-results-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 14px;
            background: rgba(20, 92, 84, .045);
            color: var(--text-muted);
            font-size: .78rem;
        }

        .satuan-modal-card {
            width: min(480px, calc(100vw - 32px));
            max-width: 480px;
        }

        .satuan-field-helper,
        .satuan-field-error {
            display: block;
            margin-top: 5px;
            font-size: .78rem;
            line-height: 1.45;
        }

        .satuan-field-helper {
            color: var(--text-muted);
        }

        .satuan-field-error {
            color: var(--status-critical);
        }

        .satuan-code-lock {
            position: relative;
        }

        .satuan-code-lock .form-control {
            padding-right: 40px;
        }

        .satuan-code-lock svg {
            position: absolute;
            top: 50%;
            right: 13px;
            color: var(--text-muted);
            transform: translateY(-50%);
            pointer-events: none;
        }

        .satuan-status-toggle {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            gap: 9px;
            color: var(--text-primary);
            cursor: pointer;
            font-weight: 600;
        }

        .satuan-status-toggle input {
            width: 18px;
            height: 18px;
            accent-color: var(--brand-primary);
        }

        .satuan-deactivate-note {
            padding: 11px 12px;
            margin-top: 12px;
            border: 1px solid rgba(184, 121, 26, .25);
            border-radius: var(--radius);
            background: rgba(184, 121, 26, .09);
            color: var(--text-primary);
            font-size: .85rem;
            line-height: 1.5;
        }

        .master-barang-actions {
            justify-content: flex-end;
        }

        .master-barang-page .master-barang-actions .btn {
            min-height: 40px;
        }

        .master-barang-modal-footer {
            flex-wrap: wrap;
        }

        .master-barang-modal-actions {
            margin-right: auto;
        }

        .master-barang-modal-scope .modal-close {
            min-width: 44px;
            min-height: 44px;
        }

        .master-barang-modal-scope .modal-card .master-barang-inline-form {
            display: inline-flex;
            flex: 0 0 auto;
            min-height: 0;
            overflow: visible;
        }

        @media (max-width: 1100px) {
            .catalog-page-header {
                align-items: flex-start;
            }

            .catalog-header-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 900px) {
            .catalog-filter-card {
                grid-template-columns: minmax(0, 1fr);
            }

            .catalog-filter-primary,
            .catalog-filter-secondary {
                display: grid;
                grid-template-columns: minmax(0, 1fr) repeat(2, minmax(0, 1fr));
                align-items: end;
                gap: 12px;
            }

            .catalog-filter-secondary {
                grid-template-columns: repeat(2, minmax(0, 1fr)) auto;
            }
        }

        @media (max-width: 700px) {
            .master-barang-page .page-header {
                align-items: flex-start;
            }

            .master-barang-header-actions {
                width: 100%;
            }

            .master-barang-header-actions .btn {
                flex: 1 1 auto;
            }

            .catalog-header-actions,
            .catalog-summary-cards {
                width: 100%;
            }

            .catalog-summary-card {
                flex: 1 1 130px;
            }

            .catalog-create-button {
                flex: 1 1 100%;
            }

            .catalog-create-toolbar {
                justify-content: stretch;
            }

            .catalog-filter-primary,
            .catalog-filter-secondary {
                grid-template-columns: minmax(0, 1fr);
            }

            .satuan-toolbar {
                grid-template-columns: minmax(0, 1fr);
            }

            .kategori-toolbar {
                grid-template-columns: minmax(0, 1fr);
            }

            .catalog-filter-actions {
                justify-content: flex-end;
            }

            .master-barang-form-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .master-barang-form-grid .form-group-full {
                grid-column: auto;
            }

            .master-barang-modal-scope {
                padding: 10px;
            }

            .master-barang-modal-scope .modal-card {
                max-height: calc(100dvh - 20px);
            }

            .kategori-drawer-header {
                padding: 14px 16px 12px;
            }

            .kategori-drawer-footer {
                align-items: flex-start;
                flex-direction: column;
            }

            .master-barang-modal-scope .modal-header,
            .master-barang-modal-scope .modal-body,
            .master-barang-modal-scope .modal-footer {
                padding-right: 16px;
                padding-left: 16px;
            }

            .master-import-backdrop {
                padding: 8px;
            }

            .master-import-dialog {
                max-height: calc(100dvh - 16px);
            }

            .master-import-dialog-header,
            .master-import-dialog-body,
            .master-import-dialog-footer {
                padding-right: 14px;
                padding-left: 14px;
            }

            .master-import-mapping,
            .master-import-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .master-import-config-grid,
            .master-import-review-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .master-import-result-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .master-import-stage {
                padding: 14px;
            }

            .master-import-dropzone {
                min-height: 96px;
                padding: 10px 12px;
            }

            .master-import-dropzone.has-file {
                min-height: 64px;
            }

            .master-import-file-selected {
                gap: 8px;
            }

            .master-import-file-selected>svg {
                width: 28px;
                height: 28px;
            }

            .master-import-ready-pill {
                padding: 4px 7px;
            }

            .master-import-mapping-row {
                grid-template-columns: minmax(0, 1fr);
                gap: 5px;
            }

            .master-import-review-heading,
            .master-import-stage-actions-between {
                align-items: stretch;
                flex-direction: column;
            }

            .master-import-stage-actions>.btn,
            .master-import-stage-actions-between>.btn {
                width: 100%;
            }

            .master-import-stepper {
                grid-template-columns: minmax(0, 1fr);
            }

            .master-import-actions {
                width: 100%;
            }

            .master-import-actions .btn {
                flex: 1 1 auto;
            }

            .master-barang-modal-actions {
                width: 100%;
            }

            .catalog-results-footer {
                align-items: flex-start;
                flex-direction: column;
            }

        }

        @media (max-width: 520px) {
            .master-data-tabs .btn {
                flex: 1 1 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .master-barang-modal-scope,
            .master-barang-modal-scope .modal-card,
            .master-barang-page .btn,
            .master-import-progress-value,
            .kategori-skeleton-line {
                scroll-behavior: auto;
                transition-duration: 0.01ms;
                animation-duration: 0.01ms;
                animation-iteration-count: 1;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $highlightCatalogText = static function (?string $value) use ($filters): \Illuminate\Support\HtmlString {
            $escapedText = e($value ?? '');
            $searchTerm = trim($filters['search'] ?? '');

            if ($searchTerm === '') {
                return new \Illuminate\Support\HtmlString($escapedText);
            }

            $escapedSearchTerm = preg_quote(e($searchTerm), '/');
            $highlightedText = preg_replace(
                '/(' . $escapedSearchTerm . ')/iu',
                '<mark class="catalog-search-match">$1</mark>',
                $escapedText,
            );

            return new \Illuminate\Support\HtmlString($highlightedText ?? $escapedText);
        };
    @endphp
    <div class="master-barang-page" x-data="{
        modal: @js($errors->any() ? old('_modal') : ($activeBarangView === 'add' ? 'create' : null)),
        catalogSearch: @js($filters['search'] ?? ''),
        unitAliases: @js($unitAliases),
        updateSatuanCreateFields(event) {
            const value = String(event.target.value ?? '').trim();
            const codeInput = document.getElementById('create-satuan-kode');
            const nameInput = document.getElementById('create-satuan-nama');
            const preview = document.getElementById('create-satuan-preview');
    
            if (!value) {
                codeInput.value = '';
                nameInput.value = '';
                nameInput.readOnly = false;
                preview.textContent = 'Kode singkat dan nama panjang akan terisi otomatis.';
                return;
            }
    
            const normalized = value.toLocaleLowerCase('id-ID');
            const alias = this.unitAliases[normalized];
            const slug = value.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .toLocaleLowerCase('id-ID').replace(/[^a-z0-9]+/g, '');
            const unit = alias ?? {
                kode: slug,
                nama: value.toLocaleLowerCase('id-ID').replace(/(^|\s)\S/g, character => character.toLocaleUpperCase('id-ID')),
            };
    
            codeInput.value = unit.kode;
            nameInput.value = unit.nama;
            nameInput.readOnly = Boolean(alias);
            preview.textContent = `Akan disimpan sebagai ${unit.kode} — ${unit.nama}.`;
        },
        updateSatuanEditFields(event, satuanId) {
            const value = String(event.target.value ?? '').trim();
            const alias = this.unitAliases[value.toLocaleLowerCase('id-ID')];
            const nameInput = document.getElementById(`edit-satuan-nama-${satuanId}`);
            const generatedName = alias?.nama ??
                value.toLocaleLowerCase('id-ID').replace(/(^|\s)\S/g, character => character.toLocaleUpperCase('id-ID'));
    
            nameInput.value = generatedName;
            nameInput.readOnly = Boolean(alias);
        },
        highlightCatalogMatch(value) {
            const text = String(value ?? '');
            const query = String(this.catalogSearch ?? '').trim();
            const escapeHtml = value => {
                const el = document.createElement('div');
                el.textContent = String(value ?? '');
                return el.innerHTML;
            };
    
            if (!query) return escapeHtml(text);
    
            const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const matcher = new RegExp(escapedQuery, 'gi');
            let html = '';
            let lastIndex = 0;
            let match;
    
            while ((match = matcher.exec(text)) !== null) {
                html += escapeHtml(text.slice(lastIndex, match.index));
                html += `<mark class='catalog-search-match'>${escapeHtml(match[0])}</mark>`;
                lastIndex = match.index + match[0].length;
            }
    
            return html + escapeHtml(text.slice(lastIndex));
        }
    }" @keydown.escape.window="modal = null">
        @if ($errors->any())
            <div class="alert-toast alert-toast-danger" role="alert" aria-labelledby="master-barang-error-title">
                <svg aria-hidden="true" class="icon-inline" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <div>
                    <strong id="master-barang-error-title">Periksa kembali data berikut:</strong>
                    <ul style="margin: 8px 0 0; padding-left: 18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <nav class="master-data-tabs" aria-label="Navigasi Master Data Barang">
            @if ($canManageMasterBarang || $canImportMasterBarang || $canCreateMasterBarang)
                <a id="master-tab-add"
                    href="{{ route('master.barang.index', ['tab' => 'barang', 'barang_view' => $canImportMasterBarang ? 'import' : 'add']) }}"
                    class="btn {{ $activeTab === 'barang' && in_array($activeBarangView, ['add', 'import'], true) ? 'btn-primary' : 'btn-outline' }}"
                    @if ($activeTab === 'barang' && in_array($activeBarangView, ['add', 'import'], true)) aria-current="page" @endif>Tambah Barang</a>
            @endif
            @if ($canViewBarang)
                <a id="master-tab-barang" href="{{ route('master.barang.index', ['tab' => 'barang']) }}"
                    class="btn {{ $activeTab === 'barang' && $activeBarangView === 'catalog' ? 'btn-primary' : 'btn-outline' }}"
                    @if ($activeTab === 'barang' && $activeBarangView === 'catalog') aria-current="page" @endif>Master Barang</a>
            @endif
            @if ($canViewKategori)
                <a id="master-tab-kategori" href="{{ route('master.barang.index', ['tab' => 'kategori']) }}"
                    class="btn {{ $activeTab === 'kategori' ? 'btn-primary' : 'btn-outline' }}"
                    @if ($activeTab === 'kategori') aria-current="page" @endif>Kategori</a>
            @endif
            @if ($canViewSatuan)
                <a id="master-tab-satuan" href="{{ route('master.barang.index', ['tab' => 'satuan']) }}"
                    class="btn {{ $activeTab === 'satuan' ? 'btn-primary' : 'btn-outline' }}"
                    @if ($activeTab === 'satuan') aria-current="page" @endif>Satuan</a>
            @endif
            @if ($canViewArea)
                <a id="master-tab-area"
                    href="{{ route('master.barang.index', ['tab' => 'area', 'area_gudang_id' => request()->query('area_gudang_id')]) }}"
                    class="btn {{ $activeTab === 'area' ? 'btn-primary' : 'btn-outline' }}"
                    @if ($activeTab === 'area') aria-current="page" @endif>User Peminta / Area</a>
            @endif
        </nav>

        @if ($activeTab === 'kategori')
            @can('kategori.create')
                <div class="catalog-create-toolbar">
                    <button type="button" class="btn btn-primary catalog-create-button" @click="modal = 'kategori-create'">
                        <svg aria-hidden="true" class="icon-inline" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2.2">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                        Tambah Kategori
                    </button>
                </div>
            @endcan
        @elseif($activeTab === 'satuan')
            @can('satuan.create')
                <div class="catalog-create-toolbar">
                    <button type="button" class="btn btn-primary catalog-create-button" @click="modal = 'satuan-create'">
                        <svg aria-hidden="true" class="icon-inline" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2.2">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                        Tambah Satuan
                    </button>
                </div>
            @endcan
        @endif

        @if ($activeTab === 'barang')
            @if ($activeBarangView === 'import' && $canImportMasterBarang)
                @include('master-data.import._form', [
                    'warehouses' => $importWarehouses,
                    'canSelectWarehouses' => $canSelectImportWarehouses,
                    'categorySuggestions' => $categorySuggestions,
                    'unitSuggestions' => $unitSuggestions,
                ])
            @else
                <section id="master-barang-panel" aria-labelledby="master-tab-barang">
                    <form method="GET" action="{{ route('master.barang.index') }}" class="card catalog-filter-card"
                        aria-label="Filter katalog master barang">
                        <input type="hidden" name="tab" value="barang">
                        <input type="hidden" name="registrasi" value="{{ $filters['registrasi'] ?? 'semua' }}">
                        <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
                        <div class="catalog-filter-primary">
                            <div class="form-group">
                                <label for="filter-search" class="form-label">Cari Barang</label>
                                <div class="catalog-search-field">
                                    <svg aria-hidden="true" class="catalog-search-icon icon-inline" width="18"
                                        height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <circle cx="11" cy="11" r="7" />
                                        <path d="m20 20-4-4" />
                                    </svg>
                                    <input id="filter-search" type="search" name="search" class="form-control"
                                        value="{{ $filters['search'] ?? '' }}"
                                        placeholder="Cari kode, nama, kategori, atau satuan"
                                        @input.debounce.400ms="$el.form.requestSubmit()">
                                </div>
                                @error('search')
                                    <span class="form-error" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="filter-kategori" class="form-label">Kategori Master</label>
                                <select id="filter-kategori" name="kategori_id" class="form-control"
                                    @change="$el.form.requestSubmit()">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($kategoriList as $kategori)
                                        <option value="{{ $kategori->id }}" @selected(($filters['kategori_id'] ?? '') == $kategori->id)>
                                            {{ $kategori->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="filter-satuan" class="form-label">Satuan Ukur</label>
                                <select id="filter-satuan" name="satuan_id" class="form-control"
                                    @change="$el.form.requestSubmit()">
                                    <option value="">Semua Satuan</option>
                                    @foreach ($satuanList as $satuan)
                                        <option value="{{ $satuan->id }}" @selected(($filters['satuan_id'] ?? '') == $satuan->id)>
                                            {{ $satuan->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="catalog-filter-secondary">
                            <div class="form-group catalog-warehouse-filter" x-data="{
                                options: @js($gudangList->map(fn($gudang) => ['id' => (string) $gudang->id, 'nama' => $gudang->nama])->values()),
                                selected: @js(array_map('strval', $filters['gudang_id'])),
                                search: '',
                                isOpen: false,
                                warehouseName(id) {
                                    return this.options.find(option => option.id === String(id))?.nama || '';
                                },
                                visibleWarehouses() {
                                    const query = this.search.trim().toLocaleLowerCase();
                                    return this.options.filter(option => option.nama.toLocaleLowerCase().includes(query));
                                },
                                toggleWarehouse(id) {
                                    const warehouseId = String(id);
                                    this.selected.includes(warehouseId) ?
                                        this.selected = this.selected.filter(selectedId => selectedId !== warehouseId) :
                                        this.selected.push(warehouseId);
                                },
                                removeWarehouse(id) {
                                    this.selected = this.selected.filter(selectedId => selectedId !== String(id));
                                }
                            }"
                                @keydown.escape.stop="isOpen = false">
                                <span id="filter-gudang-label" class="form-label">Gudang</span>
                                <div class="catalog-warehouse-control" aria-labelledby="filter-gudang-label">
                                    <template x-for="warehouseId in selected" :key="warehouseId">
                                        <span class="catalog-warehouse-chip">
                                            <span class="catalog-warehouse-chip-label"
                                                x-text="warehouseName(warehouseId)"></span>
                                            <button type="button" class="catalog-warehouse-chip-remove"
                                                :aria-label="'Hapus filter ' + warehouseName(warehouseId)"
                                                @click="removeWarehouse(warehouseId); $nextTick(() => $el.closest('form').requestSubmit())">
                                                <svg aria-hidden="true" width="13" height="13"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2.5">
                                                    <path d="m18 6-12 12M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>
                                    <button type="button" class="catalog-warehouse-picker-trigger"
                                        aria-haspopup="listbox" :aria-expanded="isOpen.toString()"
                                        @click="isOpen = !isOpen">
                                        <span x-show="selected.length === 0">Pilih satu/beberapa gudang</span>
                                        <span x-show="selected.length > 0">Tambah gudang</span>
                                    </button>
                                    <template x-for="warehouseId in selected" :key="'warehouse-input-' + warehouseId">
                                        <input type="hidden" name="gudang_id[]" :value="warehouseId">
                                    </template>
                                </div>
                                <div class="catalog-warehouse-picker" x-show="isOpen" x-cloak x-transition
                                    @click.outside="isOpen = false" role="listbox" aria-labelledby="filter-gudang-label"
                                    aria-multiselectable="true">
                                    <input type="search" class="form-control catalog-warehouse-picker-search"
                                        x-model="search" placeholder="Cari gudang…" aria-label="Cari pilihan gudang">
                                    <div class="catalog-warehouse-picker-list">
                                        <template x-for="gudang in visibleWarehouses()" :key="gudang.id">
                                            <label class="catalog-warehouse-picker-option">
                                                <input type="checkbox" :value="gudang.id"
                                                    :checked="selected.includes(gudang.id)"
                                                    @change="toggleWarehouse(gudang.id); $nextTick(() => $el.closest('form').requestSubmit())">
                                                <span x-text="gudang.nama"></span>
                                            </label>
                                        </template>
                                        <span class="catalog-warehouse-picker-empty"
                                            x-show="visibleWarehouses().length === 0">Gudang tidak ditemukan.</span>
                                    </div>
                                    <button type="button" class="catalog-warehouse-picker-clear"
                                        @click="selected = []; $nextTick(() => $el.closest('form').requestSubmit())">Hapus
                                        semua pilihan</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="filter-status" class="form-label">Status Master</label>
                                <select id="filter-status" name="status" class="form-control"
                                    @change="$el.form.requestSubmit()">
                                    <option value="">Semua Status</option>
                                    <option value="1" @selected(($filters['status'] ?? '') === '1')>Aktif</option>
                                    <option value="0" @selected(($filters['status'] ?? '') === '0')>Nonaktif</option>
                                </select>
                            </div>
                            <div class="catalog-filter-actions">
                                <a href="{{ route('master.barang.index', ['tab' => 'barang']) }}"
                                    class="btn btn-outline catalog-filter-reset" aria-label="Reset filter"
                                    title="Reset filter">
                                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path d="M3 12a9 9 0 1 0 2.64-6.36L3 8" />
                                        <path d="M3 3v5h5" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="card catalog-results-card">
                        <div class="table-responsive">
                            <table class="data-table catalog-table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="catalog-row-number">No</th>
                                        <th scope="col">Kode Barang</th>
                                        <th scope="col">Nama Barang</th>
                                        <th scope="col">Kategori Barang</th>
                                        <th scope="col">Satuan</th>
                                        <th scope="col">Gudang</th>
                                        <th scope="col" style="text-align: right;">Aksi Master</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($masterBarangs as $masterBarang)
                                        @php
                                            $editModalKey = 'edit-' . $masterBarang->id;
                                            $registerModalKey = 'register-' . $masterBarang->id;
                                            $isEditFormError = $errors->any() && old('_modal') === $editModalKey;
                                            $isRegisterFormError =
                                                $errors->any() && old('_modal') === $registerModalKey;
                                            $availableGudangs = $gudangList->reject(
                                                fn($gudang): bool => $masterBarang->barangs->contains(
                                                    'gudang_id',
                                                    $gudang->id,
                                                ),
                                            );
                                        @endphp
                                        <tr>
                                            <td class="catalog-row-number">
                                                {{ $masterBarangs->firstItem() + $loop->index }}</td>
                                            <td>
                                                <span class="catalog-code"
                                                    data-search-value="{{ $masterBarang->kode ?: '—' }}"
                                                    x-init="$el.innerHTML = highlightCatalogMatch($el.dataset.searchValue)">{!! $highlightCatalogText($masterBarang->kode ?: '—') !!}</span>
                                            </td>
                                            <td>
                                                <div class="catalog-item-heading">
                                                    <span class="catalog-item-name"
                                                        data-search-value="{{ $masterBarang->nama }}"
                                                        x-init="$el.innerHTML = highlightCatalogMatch($el.dataset.searchValue)">{!! $highlightCatalogText($masterBarang->nama) !!}</span>
                                                    @unless ($masterBarang->is_aktif)
                                                        <span class="badge badge-warning catalog-item-inactive">Nonaktif</span>
                                                    @endunless
                                                </div>
                                            </td>
                                            <td>
                                                @if ($masterBarang->kategori?->nama)
                                                    <span class="catalog-category"
                                                        data-search-value="{{ $masterBarang->kategori->nama }}"
                                                        x-init="$el.innerHTML = highlightCatalogMatch($el.dataset.searchValue)">{!! $highlightCatalogText($masterBarang->kategori->nama) !!}</span>
                                                @else
                                                    <span class="catalog-summary-note">—</span>
                                                @endif
                                            </td>
                                            <td data-search-value="{{ $masterBarang->satuan?->nama ?? '—' }}"
                                                x-init="$el.innerHTML = highlightCatalogMatch($el.dataset.searchValue)">{!! $highlightCatalogText($masterBarang->satuan?->nama ?? '—') !!}</td>
                                            <td>
                                                <div class="catalog-warehouse-list" aria-label="Gudang terdaftar">
                                                    @forelse($masterBarang->barangs as $barangLokal)
                                                        <span class="badge badge-brand"
                                                            data-search-value="{{ $barangLokal->gudang?->nama ?? 'Gudang tidak diketahui' }}"
                                                            x-init="$el.innerHTML = highlightCatalogMatch($el.dataset.searchValue)">{!! $highlightCatalogText($barangLokal->gudang?->nama ?? 'Gudang tidak diketahui') !!}</span>
                                                    @empty
                                                        <span class="catalog-warehouse-empty">Belum terdaftar di gudang
                                                            mana pun</span>
                                                    @endforelse
                                                </div>
                                            </td>
                                            <td style="text-align: right;">
                                                @canany(['barang.manage', 'barang.update'])
                                                    <div class="catalog-row-actions">
                                                        <button type="button" class="btn btn-outline btn-sm"
                                                            @click="modal = '{{ $editModalKey }}'">Edit</button>
                                                    </div>
                                                @else
                                                    <span class="catalog-summary-note">—</span>
                                                @endcanany
                                            </td>
                                        </tr>

                                        @canany(['barang.manage', 'barang.update'])
                                            <template x-teleport="body">
                                                <div class="modal-backdrop master-barang-modal-scope"
                                                    x-show="modal === '{{ $editModalKey }}'" x-cloak x-transition
                                                    @click.self="modal = null">
                                                    <section class="modal-card wide-modal" role="dialog" aria-modal="true"
                                                        aria-labelledby="edit-master-title-{{ $masterBarang->id }}">
                                                        <header class="modal-header">
                                                            <h2 id="edit-master-title-{{ $masterBarang->id }}"
                                                                class="modal-title">Edit Master Barang</h2>
                                                            <button type="button" class="modal-close"
                                                                aria-label="Tutup dialog edit"
                                                                @click="modal = null">&times;</button>
                                                        </header>

                                                        <form id="edit-master-form-{{ $masterBarang->id }}"
                                                            action="{{ route('master.barang.update', $masterBarang) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="_modal"
                                                                value="{{ $editModalKey }}">
                                                            <div class="modal-body">
                                                                <div class="master-barang-form-grid">
                                                                    <div class="form-group">
                                                                        <label for="edit-kode-{{ $masterBarang->id }}"
                                                                            class="form-label">Kode Perusahaan <span
                                                                                aria-hidden="true">*</span></label>
                                                                        <input id="edit-kode-{{ $masterBarang->id }}"
                                                                            name="kode" class="form-control"
                                                                            value="{{ $isEditFormError ? old('kode', $masterBarang->kode) : $masterBarang->kode }}"
                                                                            required maxlength="50">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="edit-nama-{{ $masterBarang->id }}"
                                                                            class="form-label">Nama Barang <span
                                                                                aria-hidden="true">*</span></label>
                                                                        <input id="edit-nama-{{ $masterBarang->id }}"
                                                                            name="nama" class="form-control"
                                                                            value="{{ $isEditFormError ? old('nama', $masterBarang->nama) : $masterBarang->nama }}"
                                                                            required maxlength="255">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="edit-kategori-{{ $masterBarang->id }}"
                                                                            class="form-label">Kategori <span
                                                                                aria-hidden="true">*</span></label>
                                                                        <select id="edit-kategori-{{ $masterBarang->id }}"
                                                                            name="kategori_id" class="form-control" required>
                                                                            @foreach ($kategoriList as $kategori)
                                                                                <option value="{{ $kategori->id }}"
                                                                                    @selected(($isEditFormError ? old('kategori_id', $masterBarang->kategori_id) : $masterBarang->kategori_id) == $kategori->id)>
                                                                                    {{ $kategori->nama }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="edit-satuan-{{ $masterBarang->id }}"
                                                                            class="form-label">Satuan <span
                                                                                aria-hidden="true">*</span></label>
                                                                        <select id="edit-satuan-{{ $masterBarang->id }}"
                                                                            name="satuan_id" class="form-control" required>
                                                                            @foreach ($satuanList as $satuan)
                                                                                <option value="{{ $satuan->id }}"
                                                                                    @selected(($isEditFormError ? old('satuan_id', $masterBarang->satuan_id) : $masterBarang->satuan_id) == $satuan->id)>
                                                                                    {{ $satuan->nama }} ({{ $satuan->kode }})
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="edit-harga-{{ $masterBarang->id }}"
                                                                            class="form-label">Harga Satuan</label>
                                                                        <input id="edit-harga-{{ $masterBarang->id }}"
                                                                            type="number" name="harga_satuan"
                                                                            class="form-control" min="0"
                                                                            step="0.01" inputmode="decimal"
                                                                            value="{{ $isEditFormError ? old('harga_satuan', $masterBarang->harga_satuan) : $masterBarang->harga_satuan }}">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label for="edit-status-{{ $masterBarang->id }}"
                                                                            class="form-label">Status</label>
                                                                        <select id="edit-status-{{ $masterBarang->id }}"
                                                                            name="is_aktif" class="form-control">
                                                                            <option value="1"
                                                                                @selected((string) ($isEditFormError ? old('is_aktif', (int) $masterBarang->is_aktif) : (int) $masterBarang->is_aktif) === '1')>Aktif</option>
                                                                            <option value="0"
                                                                                @selected((string) ($isEditFormError ? old('is_aktif', (int) $masterBarang->is_aktif) : (int) $masterBarang->is_aktif) === '0')>Nonaktif</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group form-group-full">
                                                                        <label for="edit-deskripsi-{{ $masterBarang->id }}"
                                                                            class="form-label">Deskripsi</label>
                                                                        <textarea id="edit-deskripsi-{{ $masterBarang->id }}" name="deskripsi" class="form-control" rows="3">{{ $isEditFormError ? old('deskripsi', $masterBarang->deskripsi) : $masterBarang->deskripsi }}</textarea>
                                                                    </div>

                                                                    {{-- Section Penugasan Gudang --}}
                                                                    <div class="form-group form-group-full"
                                                                        x-data="{
                                                                            registered: @js(
    $masterBarang->barangs
        ->map(
            fn($b) => [
                'id' => $b->gudang_id,
                'nama' => $b->gudang?->nama ?? 'Gudang #' . $b->gudang_id,
            ],
        )
        ->values(),
),
                                                                            available: @js(
    $availableGudangs
        ->map(
            fn($g) => [
                'id' => $g->id,
                'nama' => $g->nama,
            ],
        )
        ->values(),
),
                                                                            selectedGudangId: '',
                                                                            addWarehouse() {
                                                                                if (!this.selectedGudangId) return;
                                                                                const found = this.available.find(g => String(g.id) === String(this.selectedGudangId));
                                                                                if (found) {
                                                                                    this.registered.push(found);
                                                                                    this.available = this.available.filter(g => String(g.id) !== String(this.selectedGudangId));
                                                                                    this.selectedGudangId = '';
                                                                                }
                                                                            },
                                                                            removeWarehouse(item) {
                                                                                this.registered = this.registered.filter(g => String(g.id) !== String(item.id));
                                                                                this.available.push(item);
                                                                                this.available.sort((a, b) => a.nama.localeCompare(b.nama));
                                                                            }
                                                                        }">
                                                                        <input type="hidden" name="has_gudang_sync"
                                                                            value="1">
                                                                        <template x-for="item in registered"
                                                                            :key="item.id">
                                                                            <input type="hidden" name="gudang_ids[]"
                                                                                :value="item.id">
                                                                        </template>

                                                                        <div
                                                                            style="border-top: 1px solid var(--surface-border, #e2e8f0); padding-top: 16px; margin-top: 4px;">
                                                                            <label class="form-label"
                                                                                style="font-weight: 700; display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                                                                <span>Fasilitas Gudang Terdaftar</span>
                                                                                <span
                                                                                    style="font-size: 11px; font-weight: normal; color: var(--text-muted);"
                                                                                    x-text="registered.length + ' gudang terhubung'"></span>
                                                                            </label>
                                                                            <p
                                                                                style="margin: 0 0 10px; font-size: 12px; color: var(--text-muted);">
                                                                                Daftar gudang yang memiliki alokasi barang ini.
                                                                                Anda dapat menambahkan beberapa gudang atau
                                                                                menghapus penugasan gudang yang ada.
                                                                            </p>

                                                                            {{-- Daftar Gudang Terdaftar --}}
                                                                            <div
                                                                                style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; min-height: 42px; align-items: center; background: var(--surface-base, #f8fafc); padding: 8px 12px; border-radius: var(--radius-sm, 6px); border: 1px solid var(--surface-border, #e2e8f0);">
                                                                                <template x-if="registered.length === 0">
                                                                                    <span
                                                                                        style="font-size: 12px; color: var(--text-muted); font-style: italic;">Belum
                                                                                        terdaftar di fasilitas gudang mana pun.
                                                                                        Pilih gudang di bawah untuk
                                                                                        mendaftarkan.</span>
                                                                                </template>
                                                                                <template x-for="item in registered"
                                                                                    :key="item.id">
                                                                                    <div
                                                                                        style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: var(--surface-card, #ffffff); border: 1px solid var(--surface-border, #cbd5e1); border-radius: var(--radius-sm, 6px); font-size: 12.5px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                                                                                        <svg style="width: 14px; height: 14px; color: var(--brand-primary, #145c54); flex-shrink: 0;"
                                                                                            viewBox="0 0 24 24" fill="none"
                                                                                            stroke="currentColor"
                                                                                            stroke-width="2">
                                                                                            <path
                                                                                                d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                                                                            <polyline
                                                                                                points="9 22 9 12 15 12 15 22" />
                                                                                        </svg>
                                                                                        <span
                                                                                            style="font-weight: 600; color: var(--text-primary);"
                                                                                            x-text="item.nama"></span>
                                                                                        <button type="button"
                                                                                            @click="removeWarehouse(item)"
                                                                                            title="Hapus dari gudang ini"
                                                                                            style="background: none; border: none; cursor: pointer; color: var(--status-critical, #c1443c); padding: 0 2px; display: inline-flex; align-items: center; font-size: 16px; line-height: 1; font-weight: bold; margin-left: 2px;">&times;</button>
                                                                                    </div>
                                                                                </template>
                                                                            </div>

                                                                            {{-- Pilihan Tambah Gudang --}}
                                                                            <div
                                                                                style="display: flex; gap: 8px; align-items: center;">
                                                                                <select x-model="selectedGudangId"
                                                                                    class="form-control"
                                                                                    style="flex: 1; min-width: 0;"
                                                                                    :disabled="available.length === 0">
                                                                                    <option value="">-- Pilih Fasilitas
                                                                                        Gudang untuk Ditambahkan --</option>
                                                                                    <template x-for="g in available"
                                                                                        :key="g.id">
                                                                                        <option :value="g.id"
                                                                                            x-text="g.nama"></option>
                                                                                    </template>
                                                                                </select>
                                                                                <button type="button" class="btn btn-outline"
                                                                                    style="white-space: nowrap; flex-shrink: 0;"
                                                                                    @click="addWarehouse()"
                                                                                    :disabled="!selectedGudangId">
                                                                                    + Daftarkan ke Gudang
                                                                                </button>
                                                                            </div>
                                                                            <template
                                                                                x-if="available.length === 0 && registered.length > 0">
                                                                                <span
                                                                                    style="font-size: 11px; color: var(--text-muted); margin-top: 5px; display: block;">Seluruh
                                                                                    fasilitas gudang aktif telah terdaftar untuk
                                                                                    barang ini.</span>
                                                                            </template>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </form>

                                                        <footer class="modal-footer master-barang-modal-footer">
                                                            <div class="master-barang-modal-actions">
                                                                <form class="master-barang-inline-form"
                                                                    action="{{ route('master.barang.toggle', $masterBarang) }}"
                                                                    method="POST">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button class="btn btn-outline btn-sm"
                                                                        type="submit">{{ $masterBarang->is_aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                                                </form>
                                                                @if ($masterBarang->barangs_count === 0)
                                                                    <form class="master-barang-inline-form"
                                                                        action="{{ route('master.barang.destroy', $masterBarang) }}"
                                                                        method="POST"
                                                                        onsubmit="return confirm('Hapus master barang ini?')">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button class="btn btn-outline btn-sm"
                                                                            style="color: var(--status-critical);"
                                                                            type="submit">Hapus</button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                            <button type="button" class="btn btn-outline"
                                                                @click="modal = null">Batal</button>
                                                            <button type="submit"
                                                                form="edit-master-form-{{ $masterBarang->id }}"
                                                                class="btn btn-primary">Simpan Perubahan</button>
                                                        </footer>
                                                    </section>
                                                </div>
                                            </template>
                                        @endcan
                                    @empty
                                        <tr>
                                            <td colspan="7"
                                                style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
                                                Tidak ada barang yang cocok dengan pencarian atau filter ini. Coba ubah kata
                                                kunci atau reset filter.
                                                <div style="margin-top: 10px;">
                                                    <a href="{{ route('master.barang.index', ['tab' => 'barang']) }}"
                                                        class="btn btn-outline btn-sm">Reset Filter</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="catalog-results-footer">
                            <span class="catalog-results-summary">
                                @if ($masterBarangs->total() > 0)
                                    Menampilkan
                                    {{ number_format($masterBarangs->firstItem()) }}–{{ number_format($masterBarangs->lastItem()) }}
                                    dari {{ number_format($masterBarangs->total()) }} barang master
                                @else
                                    Menampilkan 0 barang master
                                @endif
                            </span>
                            <form method="GET" action="{{ route('master.barang.index') }}"
                                class="catalog-pagination-size">
                                <input type="hidden" name="tab" value="barang">
                                <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                                <input type="hidden" name="kategori_id" value="{{ $filters['kategori_id'] ?? '' }}">
                                <input type="hidden" name="satuan_id" value="{{ $filters['satuan_id'] ?? '' }}">
                                @foreach ($filters['gudang_id'] as $selectedGudangId)
                                    <input type="hidden" name="gudang_id[]" value="{{ $selectedGudangId }}">
                                @endforeach
                                <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
                                <input type="hidden" name="registrasi" value="{{ $filters['registrasi'] ?? 'semua' }}">
                                <label for="catalog-per-page">Baris:</label>
                                <select id="catalog-per-page" name="per_page" class="form-control">
                                    @foreach ([8, 15, 25, 50] as $pageSize)
                                        <option value="{{ $pageSize }}" @selected($filters['per_page'] === $pageSize)>
                                            {{ $pageSize }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline btn-sm">Terapkan</button>
                            </form>
                            {{ $masterBarangs->links() }}
                        </div>
                    </div>

                </section>
            @endif

            @canany(['barang.manage', 'barang.update'])
                <div class="modal-backdrop master-barang-modal-scope" x-show="modal === 'create'" x-cloak x-transition
                    @click.self="modal = null">
                    <section class="modal-card wide-modal" role="dialog" aria-modal="true"
                        aria-labelledby="create-master-title">
                        <header class="modal-header">
                            <h2 id="create-master-title" class="modal-title">Input Manual Barang</h2>
                            <button type="button" class="modal-close" aria-label="Tutup dialog tambah barang"
                                @click="modal = null">&times;</button>
                        </header>
                        <form action="{{ route('master.barang.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="_modal" value="create">
                            @if ($activeBarangView === 'add')
                                <input type="hidden" name="return_to_add" value="1">
                            @endif
                            <div class="modal-body">
                                <div class="master-barang-form-grid">
                                    <div class="form-group">
                                        <label for="new-kode" class="form-label">Kode Perusahaan <span
                                                aria-hidden="true">*</span></label>
                                        <input id="new-kode" name="kode" class="form-control"
                                            value="{{ $errors->any() && old('_modal') === 'create' ? old('kode') : '' }}"
                                            maxlength="50" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="new-nama" class="form-label">Nama Barang <span
                                                aria-hidden="true">*</span></label>
                                        <input id="new-nama" name="nama" class="form-control"
                                            value="{{ $errors->any() && old('_modal') === 'create' ? old('nama') : '' }}"
                                            maxlength="255" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="new-kategori" class="form-label">Kategori <span
                                                aria-hidden="true">*</span></label>
                                        <select id="new-kategori" name="kategori_id" class="form-control" required>
                                            <option value="">Pilih kategori</option>
                                            @foreach ($kategoriList as $kategori)
                                                <option value="{{ $kategori->id }}" @selected($errors->any() && old('_modal') === 'create' && old('kategori_id') == $kategori->id)>
                                                    {{ $kategori->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="new-satuan" class="form-label">Satuan <span
                                                aria-hidden="true">*</span></label>
                                        <select id="new-satuan" name="satuan_id" class="form-control" required>
                                            <option value="">Pilih satuan</option>
                                            @foreach ($satuanList as $satuan)
                                                <option value="{{ $satuan->id }}" @selected($errors->any() && old('_modal') === 'create' && old('satuan_id') == $satuan->id)>
                                                    {{ $satuan->nama }} ({{ $satuan->kode }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="new-harga" class="form-label">Harga Satuan</label>
                                        <input id="new-harga" type="number" name="harga_satuan" class="form-control"
                                            min="0" step="0.01" inputmode="decimal"
                                            value="{{ $errors->any() && old('_modal') === 'create' ? old('harga_satuan', 0) : 0 }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="new-foto" class="form-label">Foto Barang</label>
                                        <input id="new-foto" type="file" name="foto_barang" class="form-control"
                                            accept="image/*">
                                    </div>
                                    <div class="form-group form-group-full">
                                        <label for="new-deskripsi" class="form-label">Deskripsi</label>
                                        <textarea id="new-deskripsi" name="deskripsi" class="form-control" rows="3">{{ $errors->any() && old('_modal') === 'create' ? old('deskripsi') : '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <footer class="modal-footer">
                                <button type="button" class="btn btn-outline" @click="modal = null">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan Master Barang</button>
                            </footer>
                        </form>
                    </section>
                </div>
            @endcanany
        @endif

        @if ($activeTab === 'kategori')
            <section id="master-kategori-panel" aria-labelledby="master-tab-kategori">
                <div class="kategori-info-banner" role="note">
                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4" />
                        <path d="M12 8h.01" />
                    </svg>
                    <span>Kategori adalah pengelompokan katalog global, bukan data stok per gudang.</span>
                </div>

                <form method="GET" action="{{ route('master.barang.index') }}" class="card kategori-toolbar"
                    aria-label="Cari dan filter kategori">
                    <input type="hidden" name="tab" value="kategori">
                    <div class="form-group">
                        <label for="kategori-search" class="form-label">Cari Kategori</label>
                        <div class="catalog-search-field">
                            <svg aria-hidden="true" class="catalog-search-icon icon-inline" width="18"
                                height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-4-4" />
                            </svg>
                            <input id="kategori-search" type="search" name="search" class="form-control"
                                value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama kategori…"
                                @input.debounce.400ms="$el.form.requestSubmit()">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="kategori-status-filter" class="form-label">Status</label>
                        <select id="kategori-status-filter" name="status" class="form-control"
                            @change="$el.form.requestSubmit()">
                            <option value="">Semua Status</option>
                            <option value="1" @selected(($filters['status'] ?? '') === '1')>Aktif</option>
                            <option value="0" @selected(($filters['status'] ?? '') === '0')>Nonaktif</option>
                        </select>
                    </div>
                </form>

                <div class="card" style="padding: 0; overflow: hidden;">
                    <div class="table-responsive">
                        <table class="data-table kategori-table">
                            <thead>
                                <tr>
                                    <th scope="col">Nama Kategori</th>
                                    <th scope="col">Deskripsi</th>
                                    <th scope="col" style="text-align: right;">Jumlah Barang</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Diperbarui</th>
                                    <th scope="col" style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kategoris as $kategori)
                                    <tr>
                                        <td><span class="kategori-name">{{ $kategori->nama }}</span></td>
                                        <td>
                                            @if ($kategori->deskripsi)
                                                <span class="kategori-description"
                                                    title="{{ $kategori->deskripsi }}">{{ $kategori->deskripsi }}</span>
                                            @else
                                                <span class="kategori-description">—</span>
                                            @endif
                                        </td>
                                        <td class="kategori-usage-count">
                                            {{ number_format($kategori->master_barangs_count) }} barang</td>
                                        <td>
                                            <span
                                                class="badge {{ $kategori->is_aktif ? 'badge-success' : 'kategori-status-inactive' }}">
                                                {{ $kategori->is_aktif ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </td>
                                        <td>{{ $kategori->updated_at?->format('d M Y') ?? '—' }}</td>
                                        <td>
                                            <details class="satuan-actions">
                                                <summary aria-label="Aksi untuk kategori {{ $kategori->nama }}">
                                                    Aksi
                                                    <svg aria-hidden="true" width="14" height="14"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path d="m6 9 6 6 6-6" />
                                                    </svg>
                                                </summary>
                                                <div class="satuan-action-menu kategori-action-menu">
                                                    @can('kategori.update')
                                                        <button type="button"
                                                            @click="modal = 'kategori-edit-{{ $kategori->id }}'">Edit</button>
                                                    @endcan
                                                    <a href="{{ route('master.barang.index', ['tab' => 'kategori', 'search' => $filters['search'] ?? null, 'status' => $filters['status'] ?? null, 'kategori_drawer' => $kategori->id]) }}"
                                                        style="display: flex; min-height: 44px; align-items: center; padding: 8px 10px; border-radius: 5px; color: var(--text-primary); font-size: .82rem; text-decoration: none;">Lihat
                                                        Barang</a>
                                                    @can('kategori.update')
                                                        @if ($kategori->is_aktif)
                                                            <button type="button" class="kategori-deactivate-action"
                                                                @click="modal = 'kategori-toggle-{{ $kategori->id }}'">Nonaktifkan</button>
                                                        @else
                                                            <form
                                                                action="{{ route('master.kategori.toggle', ['kategori' => $kategori, 'tab' => 'kategori']) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit">Aktifkan</button>
                                                            </form>
                                                        @endif
                                                    @endcan
                                                </div>
                                            </details>
                                        </td>
                                    </tr>
                                    @can('kategori.update')
                                        <template x-teleport="body">
                                            <div class="modal-backdrop master-barang-modal-scope"
                                                x-show="modal === 'kategori-edit-{{ $kategori->id }}'" x-cloak x-transition
                                                @click.self="modal = null">
                                                <section class="modal-card kategori-modal-card" role="dialog"
                                                    aria-modal="true"
                                                    aria-labelledby="edit-kategori-title-{{ $kategori->id }}">
                                                    <header class="modal-header">
                                                        <h2 id="edit-kategori-title-{{ $kategori->id }}"
                                                            class="modal-title">Edit Kategori</h2>
                                                        <button type="button" class="modal-close"
                                                            aria-label="Tutup dialog edit kategori"
                                                            @click="modal = null">&times;</button>
                                                    </header>
                                                    <form
                                                        action="{{ route('master.kategori.update', ['kategori' => $kategori, 'tab' => 'kategori']) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="_modal"
                                                            value="kategori-edit-{{ $kategori->id }}">
                                                        <input type="hidden" name="is_aktif" value="0">
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label for="edit-kategori-nama-{{ $kategori->id }}"
                                                                    class="form-label">Nama Kategori <span
                                                                        aria-hidden="true">*</span></label>
                                                                <input id="edit-kategori-nama-{{ $kategori->id }}"
                                                                    name="nama" class="form-control"
                                                                    value="{{ $errors->any() && old('_modal') === 'kategori-edit-' . $kategori->id ? old('nama', $kategori->nama) : $kategori->nama }}"
                                                                    required maxlength="100">
                                                                @if ($errors->any() && old('_modal') === 'kategori-edit-' . $kategori->id && $errors->has('nama'))
                                                                    <span class="kategori-field-error"
                                                                        role="alert">{{ $errors->first('nama') }}</span>
                                                                @endif
                                                            </div>
                                                            <div class="form-group" x-data="{ description: @js($errors->any() && old('_modal') === 'kategori-edit-' . $kategori->id ? old('deskripsi', $kategori->deskripsi ?? '') : $kategori->deskripsi ?? '') }">
                                                                <label for="edit-kategori-deskripsi-{{ $kategori->id }}"
                                                                    class="form-label">Deskripsi</label>
                                                                <textarea id="edit-kategori-deskripsi-{{ $kategori->id }}" name="deskripsi" class="form-control" rows="3"
                                                                    maxlength="200" x-model="description"></textarea>
                                                                <span class="kategori-character-count"><span
                                                                        x-text="description.length">0</span>/200
                                                                    karakter</span>
                                                                @if ($errors->any() && old('_modal') === 'kategori-edit-' . $kategori->id && $errors->has('deskripsi'))
                                                                    <span class="kategori-field-error"
                                                                        role="alert">{{ $errors->first('deskripsi') }}</span>
                                                                @endif
                                                            </div>
                                                            <label class="kategori-status-toggle">
                                                                <input type="checkbox" name="is_aktif" value="1"
                                                                    @checked(
                                                                        $errors->any() && old('_modal') === 'kategori-edit-' . $kategori->id
                                                                            ? old('is_aktif', $kategori->is_aktif)
                                                                            : $kategori->is_aktif)>
                                                                <span>Status Aktif</span>
                                                            </label>
                                                            <p class="kategori-change-note">Perubahan nama kategori berlaku
                                                                untuk semua barang dan semua gudang.</p>
                                                        </div>
                                                        <footer class="modal-footer">
                                                            <button type="button" class="btn btn-outline"
                                                                @click="modal = null">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Simpan
                                                                Kategori</button>
                                                        </footer>
                                                    </form>
                                                </section>
                                            </div>
                                        </template>
                                        @if ($kategori->is_aktif)
                                            <template x-teleport="body">
                                                <div class="modal-backdrop master-barang-modal-scope"
                                                    x-show="modal === 'kategori-toggle-{{ $kategori->id }}'" x-cloak
                                                    x-transition @click.self="modal = null">
                                                    <section class="modal-card kategori-modal-card" role="dialog"
                                                        aria-modal="true"
                                                        aria-labelledby="toggle-kategori-title-{{ $kategori->id }}">
                                                        <header class="modal-header">
                                                            <h2 id="toggle-kategori-title-{{ $kategori->id }}"
                                                                class="modal-title">Nonaktifkan Kategori</h2>
                                                            <button type="button" class="modal-close"
                                                                aria-label="Tutup konfirmasi kategori"
                                                                @click="modal = null">&times;</button>
                                                        </header>
                                                        <div class="modal-body">
                                                            <p style="margin: 0 0 12px;">Kategori “{{ $kategori->nama }}”
                                                                tidak akan muncul di pilihan barang baru.</p>
                                                            @if ($kategori->active_master_barangs_count > 0)
                                                                <div class="kategori-deactivate-note">
                                                                    Kategori ini masih dipakai
                                                                    {{ number_format($kategori->active_master_barangs_count) }}
                                                                    barang aktif. Barang yang ada tidak berubah, tetapi kategori
                                                                    tidak bisa dipilih untuk barang baru.
                                                                </div>
                                                            @else
                                                                <p class="kategori-field-helper" style="margin-bottom: 0;">
                                                                    Kategori tidak akan muncul di pilihan barang baru.</p>
                                                            @endif
                                                        </div>
                                                        <footer class="modal-footer">
                                                            <button type="button" class="btn btn-outline"
                                                                @click="modal = null">Batal</button>
                                                            <form
                                                                action="{{ route('master.kategori.toggle', ['kategori' => $kategori, 'tab' => 'kategori']) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit"
                                                                    class="btn kategori-critical-button">Nonaktifkan</button>
                                                            </form>
                                                        </footer>
                                                    </section>
                                                </div>
                                            </template>
                                        @endif
                                    @endcan
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="kategori-empty-state">
                                                <svg aria-hidden="true" width="38" height="38"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="1.6">
                                                    <circle cx="11" cy="11" r="7" />
                                                    <path d="m20 20-4-4" />
                                                    <path d="M8 11h6" />
                                                </svg>
                                                <strong>Tidak ada kategori yang cocok</strong>
                                                <span>Coba kata kunci lain atau tambah kategori baru.</span>
                                                @can('kategori.create')
                                                    <button type="button" class="btn btn-primary"
                                                        @click="modal = 'kategori-create'">+ Tambah Kategori</button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <footer class="kategori-results-footer">
                        <span>
                            @if ($kategoris->total() > 0)
                                Menampilkan
                                {{ number_format($kategoris->firstItem()) }}–{{ number_format($kategoris->lastItem()) }}
                                dari {{ number_format($kategoris->total()) }} kategori
                            @else
                                Menampilkan 0 kategori
                            @endif
                        </span>
                        {{ $kategoris->links() }}
                    </footer>
                </div>

                @can('kategori.create')
                    <div class="modal-backdrop master-barang-modal-scope" x-show="modal === 'kategori-create'" x-cloak
                        x-transition @click.self="modal = null">
                        <section class="modal-card kategori-modal-card" role="dialog" aria-modal="true"
                            aria-labelledby="create-kategori-title">
                            <header class="modal-header">
                                <h2 id="create-kategori-title" class="modal-title">Tambah Kategori</h2>
                                <button type="button" class="modal-close" aria-label="Tutup dialog tambah kategori"
                                    @click="modal = null">&times;</button>
                            </header>
                            <form action="{{ route('master.kategori.store', ['tab' => 'kategori']) }}" method="POST">
                                @csrf
                                <input type="hidden" name="_modal" value="kategori-create">
                                <input type="hidden" name="is_aktif" value="0">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="create-kategori-nama" class="form-label">Nama Kategori <span
                                                aria-hidden="true">*</span></label>
                                        <input id="create-kategori-nama" name="nama" class="form-control"
                                            value="{{ $errors->any() && old('_modal') === 'kategori-create' ? old('nama') : '' }}"
                                            required maxlength="100">
                                        @if ($errors->any() && old('_modal') === 'kategori-create' && $errors->has('nama'))
                                            <span class="kategori-field-error"
                                                role="alert">{{ $errors->first('nama') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group" x-data="{ description: @js($errors->any() && old('_modal') === 'kategori-create' ? old('deskripsi', '') : '') }">
                                        <label for="create-kategori-deskripsi" class="form-label">Deskripsi</label>
                                        <textarea id="create-kategori-deskripsi" name="deskripsi" class="form-control" rows="3" maxlength="200"
                                            x-model="description"></textarea>
                                        <span class="kategori-character-count"><span x-text="description.length">0</span>/200
                                            karakter</span>
                                        @if ($errors->any() && old('_modal') === 'kategori-create' && $errors->has('deskripsi'))
                                            <span class="kategori-field-error"
                                                role="alert">{{ $errors->first('deskripsi') }}</span>
                                        @endif
                                    </div>
                                    <label class="kategori-status-toggle">
                                        <input type="checkbox" name="is_aktif" value="1" @checked(!$errors->any() || old('_modal') !== 'kategori-create' || old('is_aktif'))>
                                        <span>Status Aktif</span>
                                    </label>
                                    <p class="kategori-change-note">Perubahan nama kategori berlaku untuk semua barang dan
                                        semua gudang.</p>
                                </div>
                                <footer class="modal-footer">
                                    <button type="button" class="btn btn-outline" @click="modal = null">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                                </footer>
                            </form>
                        </section>
                    </div>
                @endcan

                @if ($categoryDrawer && $categoryBarang)
                    <div class="kategori-drawer-backdrop" @click.self="window.Turbo.visit(@js(route('master.barang.index', ['tab' => 'kategori', 'search' => $filters['search'] ?? null, 'status' => $filters['status'] ?? null])))">
                        <aside class="kategori-drawer" role="dialog" aria-modal="true"
                            aria-labelledby="kategori-drawer-title">
                            <header class="kategori-drawer-header">
                                <div>
                                    <h2 id="kategori-drawer-title">Barang dalam kategori {{ $categoryDrawer->nama }}</h2>
                                    <span class="kategori-drawer-count">{{ number_format($categoryBarang->total()) }}
                                        barang</span>
                                </div>
                                <a class="kategori-drawer-close"
                                    href="{{ route('master.barang.index', ['tab' => 'kategori', 'search' => $filters['search'] ?? null, 'status' => $filters['status'] ?? null]) }}"
                                    aria-label="Tutup panel barang kategori">&times;</a>
                            </header>
                            <form method="GET" action="{{ route('master.barang.index') }}"
                                class="kategori-drawer-search">
                                <input type="hidden" name="tab" value="kategori">
                                <input type="hidden" name="kategori_drawer" value="{{ $categoryDrawer->id }}">
                                @if (!empty($filters['search']))
                                    <input type="hidden" name="search" value="{{ $filters['search'] }}">
                                @endif
                                @if (isset($filters['status']))
                                    <input type="hidden" name="status" value="{{ $filters['status'] }}">
                                @endif
                                <div class="form-group">
                                    <label for="kategori-drawer-search" class="form-label">Cari Barang</label>
                                    <div class="catalog-search-field">
                                        <svg aria-hidden="true" class="catalog-search-icon icon-inline" width="18"
                                            height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <circle cx="11" cy="11" r="7" />
                                            <path d="m20 20-4-4" />
                                        </svg>
                                        <input id="kategori-drawer-search" type="search" name="drawer_search"
                                            class="form-control" value="{{ $filters['drawer_search'] ?? '' }}"
                                            placeholder="Cari kode atau nama barang…"
                                            @input.debounce.400ms="$el.form.requestSubmit()">
                                    </div>
                                </div>
                            </form>
                            <div class="kategori-drawer-content table-responsive">
                                <table class="data-table kategori-drawer-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Kode</th>
                                            <th scope="col">Nama Barang</th>
                                            <th scope="col">Satuan</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($categoryBarang as $masterBarang)
                                            <tr>
                                                <td><code class="satuan-code">{{ $masterBarang->kode ?: '—' }}</code>
                                                </td>
                                                <td>{{ $masterBarang->nama }}</td>
                                                <td>{{ $masterBarang->satuan?->nama ?? '—' }}</td>
                                                <td>
                                                    <span
                                                        class="badge {{ $masterBarang->is_aktif ? 'badge-success' : 'kategori-status-inactive' }}">
                                                        {{ $masterBarang->is_aktif ? 'Aktif' : 'Nonaktif' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4"
                                                    style="padding: 26px 12px; color: var(--text-muted); text-align: center;">
                                                    Tidak ada barang yang cocok dengan pencarian.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <footer class="kategori-drawer-footer">
                                <a class="kategori-drawer-link"
                                    href="{{ route('master.barang.index', ['tab' => 'barang', 'kategori_id' => $categoryDrawer->id]) }}">Buka
                                    di Master Barang</a>
                                {{ $categoryBarang->links() }}
                                <a href="{{ route('master.barang.index', ['tab' => 'kategori', 'search' => $filters['search'] ?? null, 'status' => $filters['status'] ?? null]) }}"
                                    class="btn btn-outline">Tutup</a>
                            </footer>
                        </aside>
                    </div>
                @endif
            </section>
        @endif

        @if ($activeTab === 'satuan')
            <section id="master-satuan-panel" class="master-satuan-panel" aria-labelledby="master-tab-satuan">
                <div class="satuan-info-banner" role="note">
                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4" />
                        <path d="M12 8h.01" />
                    </svg>
                    <span>Satuan bersifat global, dipakai semua gudang, dan dapat ditambah kapan pun.</span>
                </div>

                <form method="GET" action="{{ route('master.barang.index') }}" class="card satuan-toolbar"
                    aria-label="Cari dan filter satuan">
                    <input type="hidden" name="tab" value="satuan">
                    <input type="hidden" name="per_page" value="20">
                    <div class="form-group">
                        <label for="satuan-search" class="form-label">Cari Satuan</label>
                        <div class="catalog-search-field">
                            <svg aria-hidden="true" class="catalog-search-icon icon-inline" width="18"
                                height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-4-4" />
                            </svg>
                            <input id="satuan-search" type="search" name="search" class="form-control"
                                value="{{ $filters['search'] ?? '' }}" placeholder="Cari satuan atau nama panjang…">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="satuan-status-filter" class="form-label">Status</label>
                        <select id="satuan-status-filter" name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="1" @selected(($filters['status'] ?? '') === '1')>Aktif</option>
                            <option value="0" @selected(($filters['status'] ?? '') === '0')>Nonaktif</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </form>

                <div class="card" style="padding: 0; overflow: visible;">
                    <div class="table-responsive">
                        <table class="data-table satuan-table">
                            <thead>
                                <tr>
                                    <th scope="col">Satuan</th>
                                    <th scope="col">Nama Panjang</th>
                                    <th scope="col" style="text-align: right;">Dipakai oleh</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Dibuat</th>
                                    @can('satuan.update')
                                        <th scope="col" style="text-align: right;">Aksi</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($satuans as $satuan)
                                    @php
                                        $editSatuanModalKey = 'satuan-edit-' . $satuan->id;
                                        $isEditSatuanError = $errors->any() && old('_modal') === $editSatuanModalKey;
                                        $toggleSatuanModalKey = 'satuan-toggle-' . $satuan->id;
                                        $hasMasterBarangUsage = $satuan->master_barangs_count > 0;
                                    @endphp
                                    <tr>
                                        <td><code class="satuan-code">{{ $satuan->kode }}</code></td>
                                        <td style="font-weight: 700;">{{ $satuan->nama }}</td>
                                        <td class="satuan-usage-count">{{ number_format($satuan->master_barangs_count) }}
                                            barang</td>
                                        <td><span
                                                class="badge {{ $satuan->is_aktif ? 'badge-success' : 'satuan-status-inactive' }}">{{ $satuan->is_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                                        </td>
                                        <td>{{ $satuan->created_at?->format('d M Y') ?? '—' }}</td>
                                        @can('satuan.update')
                                            <td>
                                                <details class="satuan-actions">
                                                    <summary aria-label="Aksi untuk satuan {{ $satuan->kode }}">
                                                        Aksi
                                                        <svg aria-hidden="true" width="14" height="14"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2">
                                                            <path d="m6 9 6 6 6-6" />
                                                        </svg>
                                                    </summary>
                                                    <div class="satuan-action-menu">
                                                        <button type="button"
                                                            @click="modal = '{{ $editSatuanModalKey }}'">Edit</button>
                                                        @if ($satuan->is_aktif)
                                                            <button type="button" class="satuan-deactivate-action"
                                                                @click="modal = '{{ $toggleSatuanModalKey }}'">Nonaktifkan</button>
                                                        @else
                                                            <form
                                                                action="{{ route('master.satuan.toggle', ['satuan' => $satuan, 'tab' => 'satuan']) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit">Aktifkan</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </details>
                                            </td>
                                        @endcan
                                    </tr>
                                    @can('satuan.update')
                                        <template x-teleport="body">
                                            <div class="modal-backdrop master-barang-modal-scope"
                                                x-show="modal === '{{ $editSatuanModalKey }}'" x-cloak x-transition
                                                @click.self="modal = null">
                                                <section class="modal-card satuan-modal-card" role="dialog"
                                                    aria-modal="true"
                                                    aria-labelledby="edit-satuan-title-{{ $satuan->id }}">
                                                    <header class="modal-header">
                                                        <h2 id="edit-satuan-title-{{ $satuan->id }}" class="modal-title">
                                                            Edit Satuan</h2>
                                                        <button type="button" class="modal-close"
                                                            aria-label="Tutup dialog edit satuan"
                                                            @click="modal = null">&times;</button>
                                                    </header>
                                                    <form
                                                        action="{{ route('master.satuan.update', ['satuan' => $satuan, 'tab' => 'satuan']) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="_modal"
                                                            value="{{ $editSatuanModalKey }}">
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label for="edit-satuan-kode-{{ $satuan->id }}"
                                                                    class="form-label">Satuan <span
                                                                        aria-hidden="true">*</span></label>
                                                                @if ($hasMasterBarangUsage)
                                                                    <div class="satuan-code-lock">
                                                                        <input id="edit-satuan-kode-{{ $satuan->id }}"
                                                                            name="kode" class="form-control"
                                                                            value="{{ $satuan->kode }}" readonly required
                                                                            maxlength="20">
                                                                        <svg aria-hidden="true" width="17" height="17"
                                                                            viewBox="0 0 24 24" fill="none"
                                                                            stroke="currentColor" stroke-width="2">
                                                                            <rect x="3" y="11" width="18" height="11"
                                                                                rx="2" />
                                                                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                                                        </svg>
                                                                    </div>
                                                                    <small class="satuan-field-helper">Satuan tidak dapat
                                                                        diubah karena sudah dipakai
                                                                        {{ number_format($satuan->master_barangs_count) }}
                                                                        barang</small>
                                                                @else
                                                                    <input id="edit-satuan-kode-{{ $satuan->id }}"
                                                                        name="kode" class="form-control"
                                                                        value="{{ $isEditSatuanError ? old('kode', $satuan->kode) : $satuan->kode }}"
                                                                        required maxlength="20"
                                                                        @input="updateSatuanEditFields($event, '{{ $satuan->id }}')">
                                                                    <small class="satuan-field-helper">Masukkan singkatan atau
                                                                        nama yang dikenali; nama panjang akan disesuaikan
                                                                        otomatis.</small>
                                                                @endif
                                                                @if ($isEditSatuanError)
                                                                    @error('kode')
                                                                        <small
                                                                            class="satuan-field-error">{{ $message }}</small>
                                                                    @enderror
                                                                @endif
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="edit-satuan-nama-{{ $satuan->id }}"
                                                                    class="form-label">Nama Panjang <span
                                                                        aria-hidden="true">*</span></label>
                                                                <input id="edit-satuan-nama-{{ $satuan->id }}"
                                                                    name="nama" class="form-control"
                                                                    value="{{ $isEditSatuanError ? old('nama', $satuan->nama) : $satuan->nama }}"
                                                                    required maxlength="100"
                                                                    @if ($isEditSatuanError && $errors->has('nama')) aria-invalid="true" @endif>
                                                                <small class="satuan-field-helper">Contoh nama panjang:
                                                                    Kilogram</small>
                                                                @if ($isEditSatuanError)
                                                                    @error('nama')
                                                                        <small
                                                                            class="satuan-field-error">{{ $message }}</small>
                                                                    @enderror
                                                                @endif
                                                            </div>
                                                            <div class="form-group">
                                                                <input type="hidden" name="is_aktif" value="0">
                                                                <label class="satuan-status-toggle"
                                                                    for="edit-satuan-aktif-{{ $satuan->id }}">
                                                                    <input id="edit-satuan-aktif-{{ $satuan->id }}"
                                                                        type="checkbox" name="is_aktif" value="1"
                                                                        @checked($isEditSatuanError ? old('is_aktif', $satuan->is_aktif) == '1' : $satuan->is_aktif)>
                                                                    Status Aktif
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <footer class="modal-footer">
                                                            <button type="button" class="btn btn-outline"
                                                                @click="modal = null">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Simpan
                                                                Satuan</button>
                                                        </footer>
                                                    </form>
                                                </section>
                                            </div>
                                        </template>
                                    @endcan
                                    @can('satuan.update')
                                        @if ($satuan->is_aktif)
                                            <template x-teleport="body">
                                                <div class="modal-backdrop master-barang-modal-scope"
                                                    x-show="modal === '{{ $toggleSatuanModalKey }}'" x-cloak x-transition
                                                    @click.self="modal = null">
                                                    <section class="modal-card satuan-modal-card" role="dialog"
                                                        aria-modal="true"
                                                        aria-labelledby="deactivate-satuan-title-{{ $satuan->id }}">
                                                        <header class="modal-header">
                                                            <h2 id="deactivate-satuan-title-{{ $satuan->id }}"
                                                                class="modal-title">Nonaktifkan Satuan</h2>
                                                            <button type="button" class="modal-close"
                                                                aria-label="Tutup dialog nonaktifkan satuan"
                                                                @click="modal = null">&times;</button>
                                                        </header>
                                                        <div class="modal-body">
                                                            <p>Satuan '{{ $satuan->kode }}' tidak akan muncul di pilihan
                                                                barang baru. Barang yang sudah memakainya tetap aman.</p>
                                                            @if ($hasMasterBarangUsage)
                                                                <div class="satuan-deactivate-note" role="note">
                                                                    Dipakai oleh
                                                                    {{ number_format($satuan->master_barangs_count) }} barang.
                                                                    Satuan hanya dinonaktifkan, tidak dihapus.
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <footer class="modal-footer">
                                                            <button type="button" class="btn btn-outline"
                                                                @click="modal = null">Batal</button>
                                                            <form
                                                                action="{{ route('master.satuan.toggle', ['satuan' => $satuan, 'tab' => 'satuan']) }}"
                                                                method="POST">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit"
                                                                    class="btn btn-danger">Nonaktifkan</button>
                                                            </form>
                                                        </footer>
                                                    </section>
                                                </div>
                                            </template>
                                        @endif
                                    @endcan
                                @empty
                                    <tr>
                                        <td colspan="@can('satuan.update') 6 @else 5 @endcan"
                                            style="text-align: center; padding: 32px 16px; color: var(--text-muted);">
                                            @if (($filters['search'] ?? '') !== '' || ($filters['status'] ?? '') !== '')
                                                Tidak ada satuan yang sesuai dengan pencarian atau filter.
                                            @else
                                                Belum ada data satuan barang.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="satuan-results-footer">
                        <span>
                            @if ($satuans->total() > 0)
                                @if ($satuans->total() <= 20)
                                    Menampilkan {{ number_format($satuans->total()) }} satuan
                                @else
                                    Menampilkan
                                    {{ number_format($satuans->firstItem()) }}–{{ number_format($satuans->lastItem()) }}
                                    dari {{ number_format($satuans->total()) }} satuan
                                @endif
                            @else
                                Menampilkan 0 satuan
                            @endif
                        </span>
                        {{ $satuans->links() }}
                    </div>
                </div>

                @can('satuan.create')
                    <div class="modal-backdrop master-barang-modal-scope" x-show="modal === 'satuan-create'" x-cloak
                        x-transition @click.self="modal = null">
                        <section class="modal-card satuan-modal-card" role="dialog" aria-modal="true"
                            aria-labelledby="create-satuan-title">
                            <header class="modal-header">
                                <h2 id="create-satuan-title" class="modal-title">Tambah Satuan</h2>
                                <button type="button" class="modal-close" aria-label="Tutup dialog tambah satuan"
                                    @click="modal = null">&times;</button>
                            </header>
                            <form action="{{ route('master.satuan.store', ['tab' => 'satuan']) }}" method="POST">
                                @csrf
                                <input type="hidden" name="_modal" value="satuan-create">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="create-satuan-input" class="form-label">Satuan <span
                                                aria-hidden="true">*</span></label>
                                        <input id="create-satuan-input" name="satuan_input" class="form-control"
                                            value="{{ $errors->any() && old('_modal') === 'satuan-create' ? old('satuan_input', old('kode')) : '' }}"
                                            required maxlength="100" @input="updateSatuanCreateFields($event)"
                                            @if ($errors->any() && old('_modal') === 'satuan-create' && $errors->has('kode')) aria-invalid="true" @endif>
                                        <input id="create-satuan-kode" type="hidden" name="kode"
                                            value="{{ $errors->any() && old('_modal') === 'satuan-create' ? old('kode') : '' }}">
                                        <small id="create-satuan-preview" class="satuan-field-helper"
                                            aria-live="polite">Ketik nama panjang seperti “Liter” atau singkatannya seperti
                                            “ltr”; sistem akan menyiapkan pasangan satuannya.</small>
                                        @if ($errors->any() && old('_modal') === 'satuan-create')
                                            @error('kode')
                                                <small class="satuan-field-error">{{ $message }}</small>
                                            @enderror
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <label for="create-satuan-nama" class="form-label">Nama Panjang <span
                                                aria-hidden="true">*</span></label>
                                        <input id="create-satuan-nama" name="nama" class="form-control"
                                            value="{{ $errors->any() && old('_modal') === 'satuan-create' ? old('nama') : '' }}"
                                            required maxlength="100"
                                            @if ($errors->any() && old('_modal') === 'satuan-create' && $errors->has('nama')) aria-invalid="true" @endif>
                                        <small class="satuan-field-helper">Satuan yang dikenali memakai nama panjang baku;
                                            satuan baru yang belum dikenal bisa disesuaikan.</small>
                                        @if ($errors->any() && old('_modal') === 'satuan-create')
                                            @error('nama')
                                                <small class="satuan-field-error">{{ $message }}</small>
                                            @enderror
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <input type="hidden" name="is_aktif" value="0">
                                        <label class="satuan-status-toggle" for="create-satuan-aktif">
                                            <input id="create-satuan-aktif" type="checkbox" name="is_aktif"
                                                value="1" @checked($errors->any() && old('_modal') === 'satuan-create' ? old('is_aktif', '1') == '1' : true)>
                                            Status Aktif
                                        </label>
                                    </div>
                                </div>
                                <footer class="modal-footer">
                                    <button type="button" class="btn btn-outline" @click="modal = null">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan Satuan</button>
                                </footer>
                            </form>
                        </section>
                    </div>
                @endcan
            </section>
        @endif
        @if ($activeTab === 'area')
            @include('master-data.area._panel')
        @endif
    </div>
@endsection
