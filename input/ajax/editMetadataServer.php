<?php
session_start();
require("../inc/connect.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

$jaxon = jaxon();
$response = new Response();

/**
 * Cache column metadata for the metadata table.
 *
 * @return array<int, array<string, mixed>>
 */
function metadataGetColumns()
{
    static $columns = null;

    if ($columns === null) {
        global $_CONFIG;

        $schema = dbi_escape_string($_CONFIG['DATABASE']['INPUT']['name']);
        $sql = "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE, COLUMN_DEFAULT, ORDINAL_POSITION
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = '$schema'
                  AND TABLE_NAME = 'metadata'
                ORDER BY ORDINAL_POSITION";
        $result = dbi_query($sql);
        $columns = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $columns[] = $row;
            }
        }
    }

    return $columns;
}

/**
 * Cache list of nations for nationID_fk selector.
 *
 * @return array<int, array<string, mixed>>
 */
function metadataGetNations()
{
    static $nations = null;

    if ($nations === null) {
        $nations = array();
        $sql = "SELECT nationID, nation_engl FROM tbl_geo_nation ORDER BY nation_engl";
        $result = dbi_query($sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $nations[] = $row;
            }
        }
    }

    return $nations;
}

/**
 * Check whether the metadata table includes a coords column.
 *
 * @return bool
 */
function metadataHasCoordsColumn()
{
    static $hasCoords = null;

    if ($hasCoords === null) {
        $hasCoords = false;
        foreach (metadataGetColumns() as $column) {
            if (isset($column['COLUMN_NAME']) && $column['COLUMN_NAME'] === 'coords') {
                $hasCoords = true;
                break;
            }
        }
    }

    return $hasCoords;
}

/**
 * Convert a WKT POINT string to a lat,lon string.
 *
 * @param string $value
 * @return string
 */
function metadataCoordsToLatLon($value)
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (preg_match('/^POINT\\s*\\(\\s*(-?\\d+(?:\\.\\d+)?)\\s+(-?\\d+(?:\\.\\d+)?)\\s*\\)$/i', $value, $matches)) {
        $lon = $matches[1];
        $lat = $matches[2];
        return $lat . ',' . $lon;
    }

    return $value;
}

/**
 * Parse a "lat,lon" string (or WKT POINT) into numeric coords.
 *
 * @param string $raw
 * @param string|null $error
 * @return array<string, float>|null
 */
function metadataParseLatLon($raw, &$error)
{
    $error = null;
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }

    if (preg_match('/^(-?\\d+(?:\\.\\d+)?)\\s*,\\s*(-?\\d+(?:\\.\\d+)?)$/', $raw, $matches)) {
        return array('lat' => (float)$matches[1], 'lon' => (float)$matches[2]);
    }

    if (preg_match('/^POINT\\s*\\(\\s*(-?\\d+(?:\\.\\d+)?)\\s+(-?\\d+(?:\\.\\d+)?)\\s*\\)$/i', $raw, $matches)) {
        return array('lat' => (float)$matches[2], 'lon' => (float)$matches[1]);
    }

    $error = "Coords must be in 'lat,lon' format.";
    return null;
}

/**
 * Format floats for WKT output using a dot decimal separator.
 *
 * @param float $value
 * @return string
 */
function metadataFormatFloat($value)
{
    $formatted = sprintf('%.8F', (float)$value);
    $formatted = rtrim(rtrim($formatted, '0'), '.');
    if ($formatted === '-0') {
        $formatted = '0';
    }
    return $formatted;
}

/**
 * Build a Google Maps link for a coords value.
 *
 * @param string $coordsValue
 * @return string
 */
function metadataBuildMapLink($coordsValue)
{
    $error = null;
    $coords = metadataParseLatLon($coordsValue, $error);
    if ($coords === null) {
        return '';
    }

    $latText = metadataFormatFloat($coords['lat']);
    $lonText = metadataFormatFloat($coords['lon']);
    $query = rawurlencode($latText . ',' . $lonText);
    $href = "https://www.google.com/maps/search/?api=1&query=" . $query;

    return "<a class='metadata-map-link' href='" . htmlspecialchars($href, ENT_QUOTES) . "' target='_blank' rel='noopener' title='Open in Google Maps' aria-label='Open in Google Maps'>&#128205;</a>";
}

/**
 * Fetch a metadata record by id.
 *
 * @param int|null $metadataId
 * @return array<string, mixed>|null
 */
function metadataFetchRecord($metadataId)
{
    if ($metadataId === null) {
        return null;
    }

    $id = intval($metadataId);
    if (metadataHasCoordsColumn()) {
        $sql = "SELECT *, ST_AsText(coords) AS coords FROM metadata WHERE MetadataID = '$id'";
    } else {
        $sql = "SELECT * FROM metadata WHERE MetadataID = '$id'";
    }
    $result = dbi_query($sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if ($row !== null) {
            $row['__is_new'] = false;
        }
        return $row;
    }

    return null;
}

/**
 * Check whether a metadata record already exists.
 *
 * @param int $metadataId
 * @return bool
 */
function metadataRecordExists($metadataId)
{
    $metadataId = intval($metadataId);
    if ($metadataId <= 0) {
        return false;
    }

    $result = dbi_query("SELECT 1 FROM metadata WHERE MetadataID = '$metadataId' LIMIT 1");
    return ($result && mysqli_num_rows($result) > 0);
}

/**
 * Fetch the first or last MetadataID.
 *
 * @param string $order 'ASC' or 'DESC'
 * @return int|null
 */
function metadataFetchExtremeId($order)
{
    $order = ($order === 'DESC') ? 'DESC' : 'ASC';
    $sql = "SELECT MetadataID FROM metadata ORDER BY MetadataID $order LIMIT 1";
    $result = dbi_query($sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return intval($row['MetadataID']);
    }

    return null;
}

/**
 * Fetch the next or previous MetadataID relative to a given id.
 *
 * @param int $currentId
 * @param string $direction 'next' or 'prev'
 * @return int|null
 */
function metadataFetchAdjacentId($currentId, $direction)
{
    $currentId = intval($currentId);
    if ($direction === 'next') {
        $sql = "SELECT MetadataID FROM metadata WHERE MetadataID > '$currentId' ORDER BY MetadataID ASC LIMIT 1";
    } else {
        $sql = "SELECT MetadataID FROM metadata WHERE MetadataID < '$currentId' ORDER BY MetadataID DESC LIMIT 1";
    }
    $result = dbi_query($sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return intval($row['MetadataID']);
    }

    return null;
}

/**
 * Determine if the current user is part of group "editors".
 *
 * @return bool
 */
function metadataUserIsEditor()
{
    if (!isset($_SESSION['uid'])) {
        return false;
    }

    if (checkRight('admin')) {
        return true;
    }

    $userID = intval($_SESSION['uid']);
    $sql = "SELECT hg.group_name
            FROM herbarinput_log.tbl_herbardb_users hu
             JOIN herbarinput_log.tbl_herbardb_groups hg ON hu.groupID = hg.groupID
            WHERE hu.userID = '$userID'
            LIMIT 1";
    $result = dbi_query($sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return strtolower($row['group_name']) === 'editors';
    }

    return false;
}

/**
 * Render navigation controls for a pane.
 *
 * @param string $paneId 'left' or 'right'
 * @param int|null $metadataId
 * @return string
 */
function metadataRenderNavigator($paneId, $metadataId)
{
    $idValue = ($metadataId !== null) ? intval($metadataId) : '';

    $buttonsDisabled = ($metadataId === null) ? " disabled" : "";

    $html = "<div class='metadata-nav'>"
          . "<input type='hidden' id='metadata_" . $paneId . "_id' value='$idValue'>"
          . "<button type='button' class='cssfbutton' onclick=\"return metadataNavigate('$paneId','first');\"$buttonsDisabled>&laquo;</button>"
          . "<button type='button' class='cssfbutton' onclick=\"return metadataNavigate('$paneId','prev');\"$buttonsDisabled>&lsaquo;</button>"
          . "<span>ID:</span>"
          . "<input type='text' id='metadata_" . $paneId . "_jump' value='$idValue' class='metadata-id-input'>"
          . "<button type='button' class='cssfbutton' onclick=\"return metadataJump('$paneId');\">Go</button>"
          . "<button type='button' class='cssfbutton' onclick=\"return metadataNavigate('$paneId','next');\"$buttonsDisabled>&rsaquo;</button>"
          . "<button type='button' class='cssfbutton' onclick=\"return metadataNavigate('$paneId','last');\"$buttonsDisabled>&raquo;</button>";

    if ($paneId === 'right') {
        $html .= "<button type='button' class='cssfbutton' onclick=\"return metadataSyncFromLeft();\"$buttonsDisabled>&larr; copy from left</button>"
               . "<button type='button' class='cssfbutton' onclick=\"return metadataSubmit();\"$buttonsDisabled>Save</button>"
               . "<button type='button' class='cssfbutton' onclick=\"return metadataResetEdit();\"$buttonsDisabled>Reset</button>"
               . "<button type='button' class='cssfbutton' onclick=\"return metadataCreateNewRecord();\">New</button>";
    }

    $html .= "</div>";

    return $html;
}

/**
 * Render metadata fields.
 *
 * @param array<string, mixed>|null $record
 * @param string $mode 'left' or 'right'
 * @return string
 */
function metadataRenderFields($record, $mode)
{
    $columns = metadataGetColumns();
    $readonly = ($mode === 'left');
    $labelExtra = array(
        'DatasetTitle' => 'GBIF dataset title',
        'DatasetDetails' => 'GBIF dataset description',
        'LicenseText' => 'GBIF License',
    );
    $nationLookup = null;
    if ($readonly) {
        $nationLookup = array();
        foreach (metadataGetNations() as $nation) {
            if (isset($nation['nationID'])) {
                $nationLookup[(string)$nation['nationID']] = isset($nation['nation_engl']) ? (string)$nation['nation_engl'] : '';
            }
        }
    }

    $html = "<table class='metadata-field-table'>\n";
    foreach ($columns as $column) {
        $name = $column['COLUMN_NAME'];
        $value = ($record !== null && array_key_exists($name, $record)) ? $record[$name] : null;
        $label = htmlspecialchars($name);
        if (isset($labelExtra[$name])) {
            $label .= " (" . htmlspecialchars($labelExtra[$name]) . ")";
        }
        $fieldKey = htmlspecialchars($name, ENT_QUOTES);

        $rowAttr = " class='metadata-field-row' data-field='$fieldKey'";

        $html .= "<tr$rowAttr>";

        $copyButton = '';
        if ($mode === 'right') {
            if ($name === 'MetadataID') {
                $copyButton = "<button type='button' class='metadata-copy-field' data-field='$fieldKey' disabled title='MetadataID cannot be copied.'>copy from left</button>";
            } else {
                $copyButton = "<button type='button' class='metadata-copy-field' data-field='$fieldKey'>copy from left</button>";
            }
        }
        $labelHtml = "<div class='metadata-label-wrapper'>$copyButton<span class='metadata-label-text'>$label</span></div>";
        $html .= "<th>$labelHtml</th>";

        $displayValue = $value;
        if ($name === 'coords' && $value !== null && $value !== '') {
            $displayValue = metadataCoordsToLatLon((string)$value);
        }
        $dataValue = ($displayValue === null) ? '' : (string)$displayValue;
        $tdAttr = " data-value='" . htmlspecialchars($dataValue, ENT_QUOTES) . "' data-null='" . (($value === null) ? '1' : '0') . "'";

        $html .= "<td$tdAttr>";

        $mapLink = '';
        if ($name === 'coords' && $displayValue !== null && $displayValue !== '') {
            $mapLink = metadataBuildMapLink((string)$displayValue);
        }

        if ($readonly || $name === 'MetadataID') {
            if ($name === 'nationID_fk' && $value !== null && $value !== '' && is_array($nationLookup)) {
                $nationKey = (string)$value;
                if (array_key_exists($nationKey, $nationLookup) && $nationLookup[$nationKey] !== '') {
                    $display = htmlspecialchars($nationLookup[$nationKey]);
                } else {
                    $display = htmlspecialchars($nationKey);
                }
            } elseif ($name === 'coords' && $displayValue !== null && $displayValue !== '') {
                $display = htmlspecialchars((string)$displayValue);
            } else {
                $display = ($value === null || $value === '') ? "<span class='metadata-null'>NULL</span>" : nl2br(htmlspecialchars((string)$value));
            }
            $html .= $display . $mapLink;
        } else {
            $html .= metadataRenderEditableField($name, $value, $column) . $mapLink;
        }

        $html .= "</td>";
        $html .= "</tr>\n";
    }
    $html .= "</table>\n";

    return $html;
}

/**
 * Render an editable field based on column metadata.
 *
 * @param string $name
 * @param mixed $value
 * @param array<string, mixed> $column
 * @return string
 */
function metadataRenderEditableField($name, $value, $column)
{
    $dataType = strtolower($column['DATA_TYPE']);
    $columnType = strtolower($column['COLUMN_TYPE']);
    $length = isset($column['CHARACTER_MAXIMUM_LENGTH']) ? intval($column['CHARACTER_MAXIMUM_LENGTH']) : null;
    $isNullable = ($column['IS_NULLABLE'] === 'YES');
    $valueStr = ($value === null) ? '' : (string)$value;
    $valueEsc = htmlspecialchars($valueStr);
    $inputClass = "metadata-input";

    if ($name === 'coords') {
        $coordsValue = metadataCoordsToLatLon($valueStr);
        $coordsEsc = htmlspecialchars($coordsValue, ENT_QUOTES);
        return "<input class='$inputClass' type='text' name='$name' value='$coordsEsc' placeholder='lat,lon'>";
    }

    if ($name === 'nationID_fk') {
        $nations = metadataGetNations();
        $options = array();
        $options[] = "<option value=''>" . ($isNullable ? '--' : 'Select') . "</option>";
        foreach ($nations as $nation) {
            $nationId = isset($nation['nationID']) ? (string)$nation['nationID'] : '';
            $nationLabel = isset($nation['nation_engl']) ? (string)$nation['nation_engl'] : '';
            $selected = ($nationId !== '' && $nationId === $valueStr) ? " selected" : "";
            $options[] = "<option value='" . htmlspecialchars($nationId, ENT_QUOTES) . "'$selected>"
                . htmlspecialchars($nationLabel) . " (" . htmlspecialchars($nationId) . ")</option>";
        }
        return "<select class='metadata-input' name='$name'>" . implode('', $options) . "</select>";
    }

    if ($dataType === 'set') {
        $checked = (strpos($valueStr, 'digital_image') !== false) ? " checked" : "";
        return "<label><input type='checkbox' name='$name' value='digital_image'$checked> digital_image</label>";
    }

    if ($dataType === 'tinyint' && $columnType === "tinyint(4)") {
        $checked = ($valueStr !== '' && intval($valueStr) !== 0) ? " checked" : "";
        return "<input type='checkbox' name='$name' value='1'$checked>";
    }

    if ($length !== null && $length > 255) {
        $rows = ($length > 512) ? 5 : 3;
        return "<textarea class='$inputClass' name='$name' rows='$rows'>$valueEsc</textarea>";
    }

    if (in_array($dataType, array('text', 'mediumtext', 'longtext'), true)) {
        $rows = ($dataType === 'longtext') ? 8 : 5;
        return "<textarea class='$inputClass' name='$name' rows='$rows'>$valueEsc</textarea>";
    }

    $inputType = 'text';
    if (in_array($dataType, array('date', 'datetime', 'timestamp'), true)) {
        $inputType = 'text';
    } elseif (in_array($dataType, array('int', 'tinyint', 'smallint', 'mediumint', 'bigint'), true)) {
        $inputType = 'number';
    }

    $nullableClass = $isNullable ? " metadata-nullable" : "";
    return "<input class='$inputClass$nullableClass' type='$inputType' name='$name' value='$valueEsc'>";
}

/**
 * Render a complete pane.
 *
 * @param string $pane 'left' or 'right'
 * @param array<string, mixed>|null $record
 * @return string
 */
function metadataRenderPane($pane, $record)
{
    $id = ($record && isset($record['MetadataID'])) ? intval($record['MetadataID']) : null;
    $navigator = metadataRenderNavigator($pane, $id);
    $isNew = (is_array($record) && !empty($record['__is_new']));

    if ($pane === 'right') {
        $content = "<form id='metadataEditForm' onsubmit='return metadataSubmit();'>"
                 . "<input type='hidden' name='MetadataID' id='metadata_edit_id' value='" . (($id !== null) ? $id : '') . "'>"
                 . "<input type='hidden' id='metadata_is_new' value='" . ($isNew ? '1' : '0') . "'>"
                 . $navigator
                 . metadataRenderFields($record, 'right')
                 . "<div class='metadata-actions'>"
                 . "<button type='submit' class='cssfbutton'>Save</button>"
                 . "<button type='button' class='cssfbutton' onclick='return metadataResetEdit();'>Reset</button>"
                 . "</div>"
                 . "</form>";
    } else {
        $content = $navigator
                 . metadataRenderFields($record, 'left');
    }

    return $content;
}

/**
 * Build an empty record structure using column defaults.
 *
 * @param int $metadataId
 * @return array<string, mixed>
 */
function metadataBuildEmptyRecord($metadataId)
{
    $record = array('MetadataID' => intval($metadataId));
    $columns = metadataGetColumns();

    foreach ($columns as $column) {
        $name = $column['COLUMN_NAME'];
        if ($name === 'MetadataID') {
            continue;
        }

        $default = $column['COLUMN_DEFAULT'];
        $dataType = strtolower($column['DATA_TYPE']);

        if ($default === null) {
            $record[$name] = null;
        } else {
            if (in_array($dataType, array('int', 'tinyint', 'smallint', 'mediumint', 'bigint'), true)) {
                $record[$name] = intval($default);
            } else {
                $record[$name] = $default;
            }
        }
    }

    $record['__is_new'] = true;

    return $record;
}

/**
 * Load initial state for the editor.
 *
 * @param int|null $leftId
 * @param int|null $rightId
 * @return Response
 */
function metadataInit($leftId = null, $rightId = null)
{
    global $response;

    $firstId = metadataFetchExtremeId('ASC');
    if ($firstId === null) {
        $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message error'>No records found in table metadata.</div>");
        $response->assign('metadataLeftPane', 'innerHTML', '');
        $response->assign('metadataRightPane', 'innerHTML', '');
        return $response;
    }

    $messages = array();

    $leftRecord = metadataFetchRecord($leftId === null ? $firstId : $leftId);
    if ($leftRecord === null) {
        $leftRecord = metadataFetchRecord($firstId);
        $messages[] = "<div class='metadata-message warning'>Left record not found; first record loaded instead.</div>";
    }

    if ($rightId === null) {
        $rightRecord = metadataFetchRecord($leftRecord['MetadataID']);
    } else {
        $rightRecord = metadataFetchRecord($rightId);
        if ($rightRecord === null) {
            $rightRecord = metadataFetchRecord($leftRecord['MetadataID']);
            $messages[] = "<div class='metadata-message warning'>Right record not found; using left record.</div>";
        }
    }

    $response->assign('metadataLeftPane', 'innerHTML', metadataRenderPane('left', $leftRecord));
    $response->assign('metadataRightPane', 'innerHTML', metadataRenderPane('right', $rightRecord));
    $response->assign('metadataMessages', 'innerHTML', implode('', $messages));
    $response->script('metadataAfterLeftRender();');
    $response->script('metadataAfterRightRender("clean");');

    return $response;
}

/**
 * Navigate within the dataset.
 *
 * @param string $pane
 * @param int $currentId
 * @param string $direction
 * @return Response
 */
function metadataNavigate($pane, $currentId, $direction)
{
    global $response;

    $currentId = intval($currentId);
    $pane = ($pane === 'left') ? 'left' : 'right';

    $targetId = null;
    switch ($direction) {
        case 'first':
            $targetId = metadataFetchExtremeId('ASC');
            break;
        case 'last':
            $targetId = metadataFetchExtremeId('DESC');
            break;
        case 'prev':
            $targetId = metadataFetchAdjacentId($currentId, 'prev');
            break;
        case 'next':
            $targetId = metadataFetchAdjacentId($currentId, 'next');
            break;
        case 'reload':
            $targetId = $currentId;
            break;
    }

    if ($targetId === null && $direction !== 'reload') {
        $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message info'>No further record in this direction.</div>");
        return $response;
    }

    $record = metadataFetchRecord($targetId !== null ? $targetId : $currentId);
    if ($record === null) {
        if ($pane === 'right' && $direction === 'reload') {
            $record = metadataBuildEmptyRecord($currentId);
        } else {
            $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message error'>Record could not be loaded.</div>");
            return $response;
        }
    }

    $container = ($pane === 'left') ? 'metadataLeftPane' : 'metadataRightPane';
    $response->assign($container, 'innerHTML', metadataRenderPane($pane, $record));
    $response->assign('metadataMessages', 'innerHTML', '');
    if ($pane === 'left') {
        $response->script('metadataAfterLeftRender();');
    } else {
        $response->script('metadataAfterRightRender("clean");');
    }

    return $response;
}

/**
 * Jump directly to a given id.
 *
 * @param string $pane
 * @param mixed $requestedId
 * @return Response
 */
function metadataLoad($pane, $requestedId)
{
    global $response;

    $pane = ($pane === 'left') ? 'left' : 'right';
    $id = intval($requestedId);
    if (!$id) {
        $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message warning'>Please provide a valid ID.</div>");
        return $response;
    }

    $record = metadataFetchRecord($id);
    if ($record === null) {
        $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message warning'>Record with ID $id was not found.</div>");
        return $response;
    }

    $container = ($pane === 'left') ? 'metadataLeftPane' : 'metadataRightPane';
    $response->assign($container, 'innerHTML', metadataRenderPane($pane, $record));
    $response->assign('metadataMessages', 'innerHTML', '');
    if ($pane === 'left') {
        $response->script('metadataAfterLeftRender();');
    } else {
        $response->script('metadataAfterRightRender("clean");');
    }

    return $response;
}

/**
 * Copy the left dataset into the right editing pane.
 *
 * @param int $leftId
 * @return Response
 */
function metadataSyncLeftToRight($leftId, $currentRightId = null)
{
    global $response;

    $leftId = intval($leftId);
    $currentRightId = ($currentRightId !== null) ? intval($currentRightId) : null;
    $record = metadataFetchRecord($leftId);
    if ($record === null) {
        $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message warning'>Left record could not be copied.</div>");
        return $response;
    }

    if ($currentRightId !== null) {
        $record['MetadataID'] = $currentRightId;
        $record['__is_new'] = !metadataRecordExists($currentRightId);
    } else {
        $record['__is_new'] = false;
    }

    $response->assign('metadataRightPane', 'innerHTML', metadataRenderPane('right', $record));
    $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message info'>Left record copied for editing.</div>");
    $response->script('metadataAfterRightRender("dirty");');

    return $response;
}

/**
 * Prepare a new metadata record with the next available ID.
 *
 * @return Response
 */
function metadataCreateNew()
{
    global $response;

    if (!metadataUserIsEditor()) {
        $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message error'>You do not have permission to edit metadata.</div>");
        return $response;
    }

    $sql = "SELECT MAX(MetadataID) AS max_id FROM metadata WHERE MetadataID < 9999";
    $result = dbi_query($sql);
    $row = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
    $maxId = ($row && $row['max_id'] !== null) ? intval($row['max_id']) : 0;
    $nextId = $maxId + 1;

    if ($nextId <= 0) {
        $nextId = 1;
    }

    $record = metadataBuildEmptyRecord($nextId);

    $response->assign('metadataRightPane', 'innerHTML', metadataRenderPane('right', $record));
    $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message info'>New metadata record #" . htmlspecialchars((string)$nextId) . " prepared.</div>");
    $response->script('metadataAfterRightRender("dirty");');

    return $response;
}

/**
 * Persist edited values.
 *
 * @param array<string, mixed> $formData
 * @return Response
 */
function metadataSave($formData)
{
    global $response;

    if (!metadataUserIsEditor()) {
        $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message error'>You do not have permission to edit metadata.</div>");
        return $response;
    }

    if (!isset($formData['MetadataID'])) {
        $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message error'>No MetadataID was provided.</div>");
        return $response;
    }

    $metadataId = intval($formData['MetadataID']);
    if ($metadataId <= 0) {
        $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message error'>Invalid MetadataID.</div>");
        return $response;
    }

    $columns = metadataGetColumns();
    $updates = array();
    $columnValues = array('MetadataID' => "'" . $metadataId . "'");

    foreach ($columns as $column) {
        $name = $column['COLUMN_NAME'];
        if ($name === 'MetadataID') {
            continue;
        }

        $dataType = strtolower($column['DATA_TYPE']);
        $isNullable = ($column['IS_NULLABLE'] === 'YES');
        $columnType = strtolower($column['COLUMN_TYPE']);

        if ($dataType === 'point' || $name === 'coords') {
            $raw = array_key_exists($name, $formData) ? trim((string)$formData[$name]) : '';
            if ($raw === '') {
                $valueSql = "NULL";
            } else {
                $error = null;
                $coords = metadataParseLatLon($raw, $error);
                if ($coords === null) {
                    $message = ($error !== null) ? $error : 'Invalid coords value.';
                    $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message error'>" . htmlspecialchars($message) . "</div>");
                    return $response;
                }
                $lonText = metadataFormatFloat($coords['lon']);
                $latText = metadataFormatFloat($coords['lat']);
                $valueSql = "ST_GeomFromText('POINT(" . $lonText . " " . $latText . ")')";
            }
            $columnValues[$name] = $valueSql;
            $updates[] = "`$name` = $valueSql";
            continue;
        }

        if ($dataType === 'set') {
            $value = isset($formData[$name]) ? 'digital_image' : null;
        } elseif ($dataType === 'tinyint' && $columnType === "tinyint(4)") {
            $value = isset($formData[$name]) ? 1 : 0;
        } else {
            $value = array_key_exists($name, $formData) ? trim((string)$formData[$name]) : '';
        }

        $valueSql = "NULL";
        if (in_array($dataType, array('int', 'tinyint', 'smallint', 'mediumint', 'bigint'), true)) {
            $value = ($value === '' || $value === null) ? null : intval($value);
            $valueSql = ($value === null) ? "NULL" : "'" . $value . "'";
        } elseif ($dataType === 'set') {
            $valueSql = ($value === null) ? "NULL" : "'digital_image'";
        } elseif ($dataType === 'tinyint' && $columnType === "tinyint(4)") {
            $valueSql = isset($formData[$name]) ? "'1'" : "'0'";
        } else {
            if ($value === null || $value === '') {
                if ($isNullable) {
                    $valueSql = "NULL";
                } else {
                    $default = $column['COLUMN_DEFAULT'];
                    if ($default === null) {
                        $default = '';
                    }
                    $valueSql = "'" . dbi_escape_string($default) . "'";
                }
            } else {
                $valueSql = "'" . dbi_escape_string($value) . "'";
            }
        }

        $columnValues[$name] = $valueSql;
        $updates[] = "`$name` = $valueSql";
    }

    $recordExists = metadataRecordExists($metadataId);

    if ($recordExists) {
        if (empty($updates)) {
            $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message info'>No changes detected.</div>");
            $response->script('metadataMarkClean();');
            return $response;
        }

        $sql = "UPDATE metadata SET " . implode(", ", $updates) . " WHERE MetadataID = '" . $metadataId . "'";
        $result = dbi_query($sql);
        if (!$result) {
            $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message error'>Update failed.</div>");
            return $response;
        }
    } else {
        $assignments = array();
        foreach ($columnValues as $col => $val) {
            $assignments[] = "`$col` = $val";
        }
        $sql = "INSERT INTO metadata SET " . implode(", ", $assignments);
        $result = dbi_query($sql);
        if (!$result) {
            $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message error'>Insert failed.</div>");
            return $response;
        }
    }

    $record = metadataFetchRecord($metadataId);
    $response->assign('metadataRightPane', 'innerHTML', metadataRenderPane('right', $record));
    if ($recordExists) {
        $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message success'>Record $metadataId saved.</div>");
    } else {
        $response->assign('metadataMessages', 'innerHTML', "<div class='metadata-message success'>Record $metadataId inserted.</div>");
    }
    $response->script('metadataAfterRightRender("clean");');
    $response->script('var leftId = document.getElementById("metadata_left_id"); if (leftId && leftId.value) { jaxon_metadataNavigate("left", leftId.value, "reload"); }');

    return $response;
}

/**
 * register all jaxon-functions in this file
 */
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "metadataInit");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "metadataNavigate");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "metadataLoad");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "metadataCreateNew");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "metadataSyncLeftToRight");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "metadataSave");
$jaxon->processRequest();
