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
            align-items: flex-start;
            justify-content: flex-start;
            flex-wrap: wrap;
            gap: 0.5em;
        }
        .metadata-pane-title-text {
            display: flex;
            flex: 1;
            min-width: 0;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5em;
        }
        .metadata-pane-title-label {
            font-weight: bold;
        }
        .metadata-pane-title-value {
            font-weight: normal;
            color: #333;
            white-space: normal;
            overflow-wrap: anywhere;
        }
        .metadata-pane-title-value::before {
            content: " – ";
        }
        .metadata-pane-title-empty {
            display: none;
        }
        .metadata-biocase-button {
            margin-left: auto;
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
        .metadata-map-link {
            margin-left: 0.4em;
            text-decoration: none;
            font-size: 1.05em;
            line-height: 1;
        }
        .metadata-map-link:hover,
        .metadata-map-link:focus {
            opacity: 0.8;
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
        var metadataRightIsNew = false;
        var metadataBioCaseQueryTemplate =
            "<?xml version='1.0' encoding='UTF-8'?>\n" +
            "<request xmlns='http://www.biocase.org/schemas/protocol/1.3'>\n" +
            "  <header><type>search</type></header>\n" +
            "  <search>\n" +
            "    <requestFormat>http://www.tdwg.org/schemas/abcd/2.06</requestFormat>\n" +
            "    <responseFormat start='0' limit='10'>http://www.tdwg.org/schemas/abcd/2.06</responseFormat>\n" +
            "      <filter>\n" +
            "<like path='/DataSets/DataSet/Units/Unit/Identifications/Identification/Result/TaxonIdentified/ScientificName/FullScientificNameString'>A*</like>\n" +
            "      </filter>\n" +
            "      <count>false</count>\n" +
            "  </search>\n" +
            "</request>";

        function metadataSetTitleText(element, text) {
            if (!element) {
                return;
            }
            var value = (text || '').trim();
            if (value) {
                element.textContent = value;
                element.classList.remove('metadata-pane-title-empty');
            } else {
                element.textContent = '';
                element.classList.add('metadata-pane-title-empty');
            }
        }

        function metadataExtractFieldValue(pane, fieldName, options) {
            options = options || {};
            var containerId = pane === 'left' ? 'metadataLeftPane' : 'metadataRightPane';
            var container = document.getElementById(containerId);
            if (!container) {
                return '';
            }
            var row = container.querySelector('.metadata-field-row[data-field="' + fieldName + '"]');
            if (!row) {
                return '';
            }
            if (pane === 'right') {
                var input = row.querySelector('input, textarea, select');
                if (input) {
                    if (input.type === 'checkbox') {
                        return input.checked ? (input.value || '1') : '';
                    }
                    var rawValue = input.value || '';
                    return options.preserveWhitespace ? rawValue : rawValue.trim();
                }
            }
            var cell = row.querySelector('td');
            if (!cell) {
                return '';
            }
            if (cell.getAttribute('data-null') === '1') {
                return '';
            }
            var textValue = cell.textContent || '';
            return options.preserveWhitespace ? textValue : textValue.trim();
        }

        function metadataExtractDatasetTitle(pane) {
            return metadataExtractFieldValue(pane, 'DatasetTitle');
        }

        function metadataUpdateLeftHeader() {
            metadataSetTitleText(document.getElementById('metadataLeftTitle'), metadataExtractDatasetTitle('left'));
        }

        function metadataUpdateRightHeader() {
            var titleElement = document.getElementById('metadataRightTitle');
            if (!titleElement) {
                return;
            }
            if (metadataRightIsNew) {
                metadataSetTitleText(titleElement, 'New Metadata');
                return;
            }
            metadataSetTitleText(titleElement, metadataExtractDatasetTitle('right'));
        }

        function metadataRefreshRightRecordState() {
            var hidden = document.getElementById('metadata_is_new');
            metadataRightIsNew = !!(hidden && hidden.value === '1');
        }

        function metadataNormalizeSourceId(raw) {
            return (raw || '').trim().toLowerCase();
        }

        function metadataUpdateBioCaseButtonState() {
            var button = document.getElementById('metadataBioCaseButton');
            if (!button) {
                return;
            }
            var sourceId = metadataNormalizeSourceId(metadataExtractFieldValue('right', 'SourceInstitutionID'));
            var hasSource = !!sourceId;
            button.disabled = !hasSource;
            if (hasSource) {
                button.title = 'BioCASe Query for ' + sourceId;
            } else {
                button.title = 'SourceInstitutionID required';
            }
        }

        function metadataGetBioCaseQuery() {
            var custom = metadataExtractFieldValue('right', 'BioCaseQuery', { preserveWhitespace: true });
            if (custom) {
                return custom;
            }
            return metadataBioCaseQueryTemplate;
        }

        function metadataOpenBioCase() {
            var sourceId = metadataNormalizeSourceId(metadataExtractFieldValue('right', 'SourceInstitutionID'));
            if (!sourceId) {
                var messages = document.getElementById('metadataMessages');
                if (messages) {
                    messages.innerHTML = "<div class='metadata-message warning'>SourceInstitutionID fehlt.</div>";
                }
                metadataUpdateBioCaseButtonState();
                return false;
            }
            var action = "https://access.jacq.org/biocase/pywrapper.cgi?dsa=gbif_" + encodeURIComponent(sourceId);
            var query = metadataGetBioCaseQuery();
            var form = document.getElementById('metadataBioCaseForm');
            var textarea = document.getElementById('metadataBioCaseQueryField');
            if (!form || !textarea) {
                return false;
            }
            form.setAttribute('action', action);
            textarea.value = query;
            form.submit();
            return false;
        }

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
                var handleChange = function () {
                    metadataMarkDirty();
                    if (!metadataRightIsNew && el.name === 'DatasetTitle') {
                        metadataUpdateRightHeader();
                    }
                    if (el.name === 'SourceInstitutionID') {
                        metadataUpdateBioCaseButtonState();
                    }
                };
                el.addEventListener('change', handleChange);
                el.addEventListener('input', handleChange);
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
            metadataUpdateLeftHeader();
            scheduleMetadataAlign();
        }

        function metadataAfterRightRender(state) {
            metadataBindEditEvents();
            metadataRefreshRightRecordState();
            if (state === 'dirty') {
                metadataMarkDirty();
            } else {
                metadataMarkClean();
            }
            metadataUpdateRightHeader();
            metadataUpdateBioCaseButtonState();
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
            if (!metadataRightIsNew && field === 'DatasetTitle') {
                metadataUpdateRightHeader();
            }
            if (field === 'SourceInstitutionID') {
                metadataUpdateBioCaseButtonState();
            }
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
        <h2>
            <div class="metadata-pane-title-text">
                <span class="metadata-pane-title-label">Reference (read only)</span>
                <span id="metadataLeftTitle" class="metadata-pane-title-value metadata-pane-title-empty"></span>
            </div>
        </h2>
        <div id="metadataLeftPane"></div>
    </div>
    <div class="metadata-pane">
        <h2>
            <div class="metadata-pane-title-text">
                <span class="metadata-pane-title-label">Edit</span>
                <span id="metadataRightTitle" class="metadata-pane-title-value metadata-pane-title-empty"></span>
            </div>
            <button type="button" class="cssfbutton metadata-biocase-button" id="metadataBioCaseButton" onclick="return metadataOpenBioCase();" disabled>&lt;BioCASe&gt;</button>
        </h2>
        <div id="metadataRightPane"></div>
    </div>
</div>
<form id="metadataBioCaseForm" action="" method="post" target="_blank" style="display:none;">
    <textarea name="query" id="metadataBioCaseQueryField"></textarea>
</form>
</body>
</html>
