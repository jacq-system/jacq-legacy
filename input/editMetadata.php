<?php
session_start();
require("inc/connect.php");
require __DIR__ . '/vendor/autoload.php';

use Jaxon\Jaxon;

$jaxon = jaxon();
$jaxon->setOption('core.request.uri', 'ajax/editMetadataServer.php');
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "metadataInit");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "metadataNavigate");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "metadataLoad");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "metadataCreateNew");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "metadataSyncLeftToRight");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "metadataSave");

$userHasAccess = checkRight('admin');

if (!$userHasAccess) {
    ?><!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Metadata Editor</title>
        <link rel="stylesheet" type="text/css" href="css/screen.css">
        <style>
            body {
                font-family: sans-serif;
                margin: 2em;
            }
            .metadata-message.error {
                color: #b30000;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <h1>Metadata Editor</h1>
        <p class="metadata-message error">You do not have permission to access this module.</p>
    </body>
    </html>
    <?php
    exit();
}

?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Metadata Editor</title>
    <link rel="stylesheet" type="text/css" href="css/screen.css">
    <style>
        body {
            font-family: sans-serif;
            margin: 1.5em;
        }
        h1 {
            margin-bottom: 0.5em;
        }
        .metadata-intro {
            margin-bottom: 1.5em;
            color: #333;
        }
        #metadataEditor {
            display: flex;
            gap: 1.5em;
            align-items: flex-start;
        }
        .metadata-pane {
            flex: 1;
            min-width: 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 1em;
            background-color: #fdfdfd;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .metadata-pane h2 {
            margin-top: 0;
            font-size: 1.2em;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 0.4em;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .metadata-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4em;
            align-items: center;
            margin-bottom: 0.8em;
        }
        .metadata-nav span {
            font-weight: bold;
        }
        .metadata-id-input {
            width: 5em;
            padding: 0.2em 0.4em;
        }
        .metadata-field-table {
            width: 100%;
            border-collapse: collapse;
        }
        .metadata-field-table th,
        .metadata-field-table td {
            border-bottom: 1px solid #eee;
            padding: 0.3em 0.4em;
            vertical-align: top;
        }
        .metadata-field-table th {
            text-align: right;
            font-weight: normal;
            color: #555;
            width: 35%;
        }
        .metadata-field-table td {
            width: 65%;
        }
        .metadata-field-table textarea,
        .metadata-field-table input[type="text"],
        .metadata-field-table input[type="number"] {
            width: 100%;
            box-sizing: border-box;
        }
        .metadata-field-table textarea {
            resize: vertical;
        }
        .metadata-null {
            color: #888;
            font-style: italic;
        }
        .metadata-actions {
            margin-top: 1em;
            display: flex;
            gap: 0.6em;
        }
        .metadata-message {
            margin-bottom: 1em;
            padding: 0.6em 0.8em;
            border-radius: 4px;
        }
        .metadata-message.info {
            background-color: #eef5ff;
            border: 1px solid #aac4ff;
            color: #104a8b;
        }
        .metadata-message.warning {
            background-color: #fff7e0;
            border: 1px solid #ffd26f;
            color: #7a5a00;
        }
        .metadata-message.error {
            background-color: #ffecec;
            border: 1px solid #ff6b6b;
            color: #a40000;
        }
        .metadata-message.success {
            background-color: #ecfff0;
            border: 1px solid #6fd989;
            color: #1c6b34;
        }
        button.cssfbutton {
            padding: 0.2em 0.6em;
            font-weight: bold;
            border-width: 2px;
            border-style: solid;
            border-color: #7fbf7f;
            background-color: #dff5df;
            cursor: pointer;
        }
        button.cssfbutton:hover {
            background-color: #abffab;
        }
        .metadata-pane form {
            margin: 0;
        }
        .metadata-field-table {
            table-layout: fixed;
        }
        .metadata-field-table th {
            white-space: nowrap;
        }
        .metadata-label-wrapper {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 0.4em;
        }
        .metadata-label-text {
            display: inline-block;
            max-width: 100%;
        }
        .metadata-copy-field {
            font-size: 0.7em;
            padding: 0.15em 0.5em;
            border: 1px solid #7fbf7f;
            background-color: #f2fff2;
            color: #205420;
            border-radius: 3px;
            cursor: pointer;
        }
        .metadata-copy-field:hover {
            background-color: #d4f8d4;
        }
        .metadata-copy-field:disabled,
        .metadata-copy-field:disabled:hover {
            background-color: #f0f0f0;
            border-color: #cccccc;
            color: #888888;
            cursor: default;
        }
        @media (max-width: 1200px) {
            #metadataEditor {
                flex-direction: column;
            }
        }
    </style>
    <?php echo $jaxon->getScript(true, true); ?>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"
            integrity="sha256-3gJwYp4H7tYsJh8e3toDnN/SfOcDSBxPTT+3BxBqz0k="
            crossorigin="anonymous"></script>
    <script>
        var metadataDirty = false;

        function metadataMarkDirty() {
            metadataDirty = true;
        }

        function metadataMarkClean() {
            metadataDirty = false;
        }

        function metadataBindEditEvents() {
            var form = document.getElementById('metadataEditForm');
            if (!form) {
                return;
            }
            var elements = form.querySelectorAll('input, textarea, select');
            elements.forEach(function (el) {
                if (el.name === 'MetadataID') {
                    return;
                }
                if (el.dataset.dirtyBound === '1') {
                    return;
                }
                el.addEventListener('change', metadataMarkDirty);
                el.addEventListener('input', metadataMarkDirty);
                el.dataset.dirtyBound = '1';
            });
        }

        function metadataConfirmDiscard() {
            if (!metadataDirty) {
                return true;
            }
            return confirm('You have unsaved changes. Continue without saving?');
        }

        function metadataAfterLeftRender() {
            scheduleMetadataAlign();
        }

        function metadataAfterRightRender(state) {
            metadataBindEditEvents();
            if (state === 'dirty') {
                metadataMarkDirty();
            } else {
                metadataMarkClean();
            }
            scheduleMetadataAlign();
        }

        function metadataNavigate(pane, direction) {
            var idField = document.getElementById('metadata_' + pane + '_id');
            var currentId = idField ? parseInt(idField.value, 10) || 0 : 0;
            if (pane === 'right' && direction !== 'reload') {
                if (!metadataConfirmDiscard()) {
                    return false;
                }
            }
            jaxon_metadataNavigate(pane, currentId, direction);
            return false;
        }

        function metadataJump(pane) {
            var input = document.getElementById('metadata_' + pane + '_jump');
            if (!input) {
                return false;
            }
            var value = input.value.trim();
            if (pane === 'right') {
                if (!metadataConfirmDiscard()) {
                    return false;
                }
            }
            jaxon_metadataLoad(pane, value);
            return false;
        }

        function metadataSyncFromLeft() {
            var hidden = document.getElementById('metadata_left_id');
            if (!hidden) {
                return false;
            }
            var leftId = parseInt(hidden.value, 10) || 0;
            if (!leftId) {
                document.getElementById('metadataMessages').innerHTML = "<div class='metadata-message warning'>No reference record loaded.</div>";
                return false;
            }
            if (metadataDirty && !metadataConfirmDiscard()) {
                return false;
            }
            var rightHidden = document.getElementById('metadata_edit_id');
            var rightId = rightHidden ? (parseInt(rightHidden.value, 10) || 0) : 0;
            metadataMarkDirty();
            jaxon_metadataSyncLeftToRight(leftId, rightId);
            return false;
        }

        function metadataResetEdit() {
            var hidden = document.getElementById('metadata_edit_id');
            if (!hidden) {
                return false;
            }
            if (!metadataConfirmDiscard()) {
                return false;
            }
            var editId = parseInt(hidden.value, 10) || 0;
            if (!editId) {
                return false;
            }
            jaxon_metadataNavigate('right', editId, 'reload');
            return false;
        }

        function metadataCreateNewRecord() {
            if (!metadataConfirmDiscard()) {
                return false;
            }
            jaxon_metadataCreateNew();
            return false;
        }

        function metadataSubmit() {
            jaxon_metadataSave(jaxon.getFormValues('metadataEditForm'));
            return false;
        }

        function metadataCopyField(field) {
            if (!field || field === 'MetadataID') {
                return;
            }
            var leftCell = document.querySelector('#metadataLeftPane .metadata-field-row[data-field="' + field + '"] td');
            var rightElement = document.querySelector('#metadataRightPane [name="' + field + '"]');
            if (!leftCell || !rightElement) {
                return;
            }
            var isNull = leftCell.getAttribute('data-null') === '1';
            var value = leftCell.getAttribute('data-value') || '';

            if (rightElement.type === 'checkbox') {
                if (rightElement.value === 'digital_image') {
                    rightElement.checked = !isNull && value.indexOf('digital_image') !== -1;
                } else {
                    rightElement.checked = !isNull && value !== '' && value !== '0';
                }
            } else {
                rightElement.value = isNull ? '' : value;
            }
            metadataMarkDirty();
            scheduleMetadataAlign();
        }

        function alignMetadataRows() {
            var leftRows = document.querySelectorAll('#metadataLeftPane .metadata-field-table tr');
            var rightRows = document.querySelectorAll('#metadataRightPane .metadata-field-table tr');
            var rowCount = Math.min(leftRows.length, rightRows.length);

            for (var i = 0; i < rowCount; i++) {
                leftRows[i].style.height = 'auto';
                rightRows[i].style.height = 'auto';
            }

            for (var j = 0; j < rowCount; j++) {
                var maxHeight = Math.max(leftRows[j].offsetHeight, rightRows[j].offsetHeight);
                leftRows[j].style.height = maxHeight + 'px';
                rightRows[j].style.height = maxHeight + 'px';
            }
        }

        function scheduleMetadataAlign() {
            window.setTimeout(alignMetadataRows, 0);
        }

        document.addEventListener('click', function (event) {
            if (event.target.classList && event.target.classList.contains('metadata-copy-field')) {
                event.preventDefault();
                if (event.target.disabled) {
                    return false;
                }
                var field = event.target.getAttribute('data-field');
                metadataCopyField(field);
                return false;
            }
            return true;
        });

        window.addEventListener('resize', scheduleMetadataAlign);

        window.addEventListener('beforeunload', function (event) {
            if (!metadataDirty) {
                return;
            }
            event.preventDefault();
            event.returnValue = '';
        });

        document.addEventListener('DOMContentLoaded', function () {
            jaxon_metadataInit();
        });
    </script>
</head>
<body>
<h1>Metadata Editor</h1>
<p class="metadata-intro">
    The left column shows a reference record (read only); the right column lets you edit a record.
    Use the navigation bars to browse or jump to specific IDs independently on each side.
</p>
<div id="metadataMessages"></div>
<div id="metadataEditor">
    <div class="metadata-pane">
        <h2>Reference (read only)</h2>
        <div id="metadataLeftPane"></div>
    </div>
    <div class="metadata-pane">
        <h2>Edit</h2>
        <div id="metadataRightPane"></div>
    </div>
</div>
</body>
</html>
