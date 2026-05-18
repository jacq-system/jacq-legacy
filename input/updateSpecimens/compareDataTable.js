class CompareDataTable {
    /**
    * creates an html table on the current DOM to compare data from two different sources (database- & importData i.E.)
    * the id is needed to compare the data correctly -> first element of the row needs to be the id
    * databaseData[] must be <= importData[]
    **/
    constructor(id) {
        // param id: represents the id for the html table element; checking existence
        if (document.getElementById(id)) {
            throw "Can't create HTML element with ID \"{}\" - ID already exists in the current DOM!".format(id);
        }
        this.id = id
        this.selectedColor = "lightgrey";
        this.unselectedColor = "white";
    }

    setHeader(header) {
        // param header: list/array of headers shown as column-titles on page
        if (typeof(this.dataStructure) !== 'undefined') {
            this.dataStructure.header = header;
        } else {
            this.header = header;
        }
    }

    setImportData(importData) {
        this.importData = importData;
    }

    setDatabaseData(databaseData) {
        this.databaseData = databaseData;
    }

    buildDataStructure() {
        /**
        * building a data structure as base for further operations
        * assign import and database datasets by id
        **/
        if (typeof(this.header) === 'undefined') {
            throw "No header defined!";
        }
        if (typeof(this.importData) === 'undefined') {
            throw "No import data defined!";
        }
        if (typeof(this.databaseData) === 'undefined') {
            throw "No database data defined!";
        }
        if (this.databaseData.length > this.importData.length) {
            throw "database data can't have a bigger size than import data";
        }

        this.dataStructure = {
            header: this.header,
            datasets: []
        }

        for (var i = 0; i < this.importData.length; i++) {
            /**
            * for every dataset in importData:
            *    - get id from dataset[0]
            *    - set id in datasetTemplate
            *    - check if databaseData contains a dataset with same id
            *    - set both datasets in datasetTemplate
            **/

            var currImportData = this.importData[i];
            var currId = currImportData[0];
            var currDataset = {
                id: currId,
                importData: currImportData,
                databaseData: null
            }

            for (var j = 0; j < this.databaseData.length; j++) {
                // search for id in database-dataset; assign data if successful
                if (this.databaseData[j][0] == currId) {
                    currDataset.databaseData = this.databaseData[j];
                    break;
                }
            }
            this.dataStructure.datasets.push(currDataset);
        }
    }

    buildTable() {
        this.buildDataStructure();

        this.chosenData = {};

        // preselect all import data as chosen
        for (let i = 0; i < this.dataStructure.datasets.length; i++) {
            let tmp = this.dataStructure.datasets[i];
            this.chosenData[tmp.id] = tmp.importData;
        }

        this.htmlElements = {
            table: null,
            header: {
                row: null,
                cells: []
            },
            datasets: []
        };
        var cell;

        // build table element
        this.htmlElements.table = document.createElement("table");
        this.htmlElements.table.id = this.id;
        this.htmlElements.table.style.borderSpacing = "0px";
        document.body.appendChild(this.htmlElements.table);

        // build header-row
        this.htmlElements.header.row = document.createElement("tr");
        for (let i = 0; i < this.dataStructure.header.length; i++) {
            cell = document.createElement("th");
            cell.innerHTML = this.dataStructure.header[i];
            cell.style.borderBottom = "2px solid black";
            this.htmlElements.header.cells.push(cell);
            this.htmlElements.header.row.appendChild(cell);
        }
        this.htmlElements.table.appendChild(this.htmlElements.header.row);

        // build each row cell by cell
        for (let i = 0; i < this.dataStructure.datasets.length; i++) {
            let currDataset = this.dataStructure.datasets[i];
            let currDataRow = {
                id: currDataset.id,
                importRow: {
                    row: document.createElement("tr"),
                    cells: []
                },
                databaseRow: {
                    row: null,
                    cells: []
                }
            }
            for (let j = 0; j < currDataset.importData.length; j++) {
                cell = document.createElement("td");
                cell.innerHTML = currDataset.importData[j];
                cell.style.background = this.selectedColor;
                if (j == 0) {
                    cell.style.fontWeight = "bold";
                }
                if (currDataset.databaseData === null) {
                    cell.style.borderBottom = "2px solid black";
                }
                currDataRow.importRow.row.appendChild(cell);
                currDataRow.importRow.cells.push(cell);
            }
            this.htmlElements.table.appendChild(currDataRow.importRow.row);

            // if there is a database-dataset it will displayed in the next row
            if (currDataset.databaseData !== null) {
                currDataRow.databaseRow.row = document.createElement("tr");

                for (let j = 0; j < currDataset.databaseData.length; j++) {
                    cell = document.createElement("td");
                    cell.innerHTML = currDataset.databaseData[j];
                    cell.style.background = this.unselectedColor;
                    if (j == 0) {
                        cell.style.fontWeight = "bold";
                    }
                    cell.style.borderBottom = "2px solid black";
                    currDataRow.databaseRow.row.appendChild(cell);
                    currDataRow.databaseRow.cells.push(cell);
                }
                this.htmlElements.table.appendChild(currDataRow.databaseRow.row);
            }
            this.htmlElements.datasets.push(currDataRow);
        }
        // add events for each element
        this.applyEvents();
    }

    applyEvents() {
        var that = this; // to access event-function scope on 'this'

        // iterate row by row and cell for cell to make each cell selectable and unselect the opposit data (import|database)
        for (let i = 0; i < this.htmlElements.datasets.length; i++) {
            var currDataset = this.htmlElements.datasets[i];
            if (currDataset.databaseRow.row !== null) {
                for (let j = 1; j < currDataset.importRow.cells.length; j++) {
                    let currImportCell = currDataset.importRow.cells[j];
                    let currDatabaseCell = currDataset.databaseRow.cells[j];
                    let id = currDataset.importRow.cells[0].innerHTML;
                    currImportCell.onclick = function () {
                        that.chosenData[id][j] = currImportCell.innerHTML;
                        currImportCell.style.background = that.selectedColor;
                        currDatabaseCell.style.background = that.unselectedColor;
                    }
                    currDatabaseCell.onclick = function () {
                        that.chosenData[id][j] = currDatabaseCell.innerHTML;
                        currDatabaseCell.style.background = that.selectedColor;
                        currImportCell.style.background = that.unselectedColor;
                    }
                }
            }
        }
        // id cell is clickable to select the whole row at once; unselect the opposit row if exists
        for (let i = 0; i < this.htmlElements.datasets.length; i++) {
            let currDataset = this.htmlElements.datasets[i];
            if (currDataset.databaseRow.row !== null) {
                currDataset.importRow.cells[0].onclick = function () {
                    let id = currDataset.importRow.cells[0].innerHTML;
                    for (let j = 0; j < currDataset.importRow.cells.length; j++) {
                        currDataset.importRow.cells[j].style.background = that.selectedColor;
                        currDataset.databaseRow.cells[j].style.background = that.unselectedColor;
                        that.chosenData[id][j] = currDataset.importRow.cells[j].innerHTML;
                    }
                }
                currDataset.databaseRow.cells[0].onclick = function () {
                    let id = currDataset.importRow.cells[0].innerHTML;
                    for (let j = 0; j < currDataset.databaseRow.cells.length; j++) {
                        currDataset.databaseRow.cells[j].style.background = that.selectedColor;
                        currDataset.importRow.cells[j].style.background = that.unselectedColor;
                        that.chosenData[id][j] = currDataset.databaseRow.cells[j].innerHTML;
                    }
                }
            }
        }
        // headers clickable to toggle database value or import value for each row in the clicked column
        // set header-cells data-attribute to "headerSelection" to save the current toggle state
        var rows = this.htmlElements.datasets;
        for (let i = 0; i < this.htmlElements.header.cells.length; i++) {
            let currCell = this.htmlElements.header.cells[i];
            currCell.setAttribute("data-headerSelection", 1);
            currCell.onclick = function () {
                if (currCell.getAttribute("data-headerSelection") == 0) {
                    for (let j = 0; j < rows.length; j++) {
                        if (rows[j].databaseRow.row !== null) {
                            let id = rows[j].importRow.cells[0].innerHTML;
                            rows[j].importRow.cells[i].style.background = that.selectedColor;
                            rows[j].databaseRow.cells[i].style.background = that.unselectedColor;
                            that.chosenData[id][i] = rows[j].importRow.cells[i].innerHTML;
                        }
                    }
                }
                if (currCell.getAttribute("data-headerSelection") == 1) {
                    for (let j = 0; j < rows.length; j++) {
                        if (rows[j].databaseRow.row !== null) {
                            let id = rows[j].importRow.cells[0].innerHTML;
                            rows[j].importRow.cells[i].style.background = that.unselectedColor;
                            rows[j].databaseRow.cells[i].style.background = that.selectedColor;
                            that.chosenData[id][i] = rows[j].databaseRow.cells[i].innerHTML;
                        }
                    }
                }
                if (currCell.getAttribute("data-headerSelection") == 0) {
                    currCell.setAttribute("data-headerSelection", 1);
                } else {
                    currCell.setAttribute("data-headerSelection", 0);
                }
            }
        }
    }

    returnChosenData () {
        return (this.chosenData);
    }
}
