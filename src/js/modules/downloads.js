'use strict';
var Tab = require('../tab');

/**
 * Calculate the filesize into KB and MB
 * @param size
 * @returns {string}
 */
function formatSize(size) {
  var oneMb = 1048576;
  var fileSize = parseInt(size, 10);
  var kBFileSize = Math.round(fileSize / 1024);
  var mBFileSIze = Math.round(fileSize / 1024 / 1024);
  if (!size) {
    return ' -- ';
  }
  // in case, the filesize is smaller than 1MB,
  // the format will be rendered in KB
  // otherwise in MB
  return (fileSize < oneMb) ?
    kBFileSize  + ' KB' :
    mBFileSIze + ' MB';
}

var createRow = function (element) {
  var row = $('<dt class="filename">' + element.assetTitle+ '</dt>' +
    '<dd class="size">' + formatSize(element.size) + '</dd>');
  //render and append
  this.append(row);
  var openFileButton = createListButton("file-open", "pwp-outgoing", "Open");
  this.append(openFileButton);
  openFileButton.click(function () {
    window.open(element.url, 'Podlove Popup', 'width=550,height=420,resizable=yes');
    return false;
  });

  var fileInfoButton = createListButton("file-info", "pwp-info-circled", "Info");
  this.append(fileInfoButton);
  fileInfoButton.click(function () {
    window.prompt('file URL:', element.downloadUrl);
    return false;
  });

};

var createListButton = function(className, icon, title) {
  return $('<dd class="' + className + '">' +
      '<a href="#" class="button button-toggle ' + icon + '" title="' + title + '"></a>' +
    '</dd>');
};

/**
 *
 * @param {object} params
 * @constructor
 */
function Downloads (params) {
  this.list = this.createList(params);
  this.tab = this.createDownloadTab(params);
}

/**
 *
 * @param {object} params
 * @returns {null|Tab} download tab
 */
Downloads.prototype.createDownloadTab = function (params) {
  if ((!params.downloads && !params.sources) || params.hidedownloadbutton === true) {
    return null;
  }
  var downloadTab = new Tab({
      icon: "pwp-download",
      title: "Show/hide download bar",
      name: 'downloads',
      headline: 'Download',
      active: !!params.downloadbuttonsVisible
    }),
    $listElement = downloadTab.createSection('<dl></dl>');

  this.list.forEach(createRow, $listElement);
  downloadTab.box.append($listElement);

  return downloadTab;
};

Downloads.prototype.createList = function (params) {
  if (params.downloads && params.downloads[0].assetTitle) {
    return params.downloads
  }

  if (params.downloads) {
    return params.downloads.map(function (element) {
      return {
        "assetTitle": element.name,
        "downloadUrl": element.dlurl,
        "url": element.url
      };
    });
  }
  // build from source elements
  return params.sources.map(function (element) {
    var parts = element.split('.');
    return {
      url: element,
      dlurl: element,
      name: parts[parts.length - 1]
    };
  });
};

module.exports = Downloads;

