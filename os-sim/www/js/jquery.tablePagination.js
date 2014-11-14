/**
 * tablePagination - A table plugin for jQuery that creates pagination elements
 *
 * http://neoalchemy.org/tablePagination.html
 *
 * Copyright (c) 2009 Ryan Zielke (neoalchemy.com)
 * licensed under the MIT licenses:
 * http://www.opensource.org/licenses/mit-license.php
 *
 * @name tablePagination
 * @type jQuery
 * @param Object settings;
 *      firstArrow - Image - Pass in an image to replace default image. Default: (new Image()).src="./images/first.gif"
 *      prevArrow - Image - Pass in an image to replace default image. Default: (new Image()).src="./images/prev.gif"
 *      lastArrow - Image - Pass in an image to replace default image. Default: (new Image()).src="./images/last.gif"
 *      nextArrow - Image - Pass in an image to replace default image. Default: (new Image()).src="./images/next.gif"
 *      rowsPerPage - Number - used to determine the starting rows per page. Default: 5
 *      currPage - Number - This is to determine what the starting current page is. Default: 1
 *      optionsForRows - Array - This is to set the values on the rows per page. Default: [5,10,25,50,100]
 *      ignoreRows - Array - This is to specify which 'tr' rows to ignore. It is recommended that you have those rows be invisible as they will mess with page counts. Default: []
 *
 *
 * @author Ryan Zielke (neoalchemy.org)
 * @version 0.2
 * @requires jQuery v1.2.3 or above
 */

 (function($){

	$.fn.tablePagination = function(settings) {
		var defaults = {  
			firstArrow : (new Image()).src="/ossim/pixmaps/first.gif",  
			prevArrow : (new Image()).src="/ossim/pixmaps/prev.gif",
			lastArrow : (new Image()).src="/ossim/pixmaps/last.gif",
			nextArrow : (new Image()).src="/ossim/pixmaps/next.gif",
			rowsPerPage : 5,
			currPage : 1,
			optionsForRows : [5,10,25,50,100],
			ignoreRows : []
		};  
		settings = $.extend(defaults, settings);
		
		return this.each(function() {
      var table = $(this)[0];
      var totalPagesId, currPageId, rowsPerPageId, firstPageId, prevPageId, nextPageId, lastPageId
      if (table.id) {
        totalPagesId = '#'+table.id+'+#tablePagination #tablePagination_totalPages';
        currPageId = '#'+table.id+'+#tablePagination #tablePagination_currPage';
        rowsPerPageId = '#'+table.id+'+#tablePagination #tablePagination_rowsPerPage';
        firstPageId = '#'+table.id+'+#tablePagination #tablePagination_firstPage';
        prevPageId = '#'+table.id+'+#tablePagination #tablePagination_prevPage';
        nextPageId = '#'+table.id+'+#tablePagination #tablePagination_nextPage';
        lastPageId = '#'+table.id+'+#tablePagination #tablePagination_lastPage';
      }
      else {
        totalPagesId = '#tablePagination #tablePagination_totalPages';
        currPageId = '#tablePagination #tablePagination_currPage';
        rowsPerPageId = '#tablePagination #tablePagination_rowsPerPage';
        firstPageId = '#tablePagination #tablePagination_firstPage';
        prevPageId = '#tablePagination #tablePagination_prevPage';
        nextPageId = '#tablePagination #tablePagination_nextPage';
        lastPageId = '#tablePagination #tablePagination_lastPage';
      }
      
      var possibleTableRows = $.makeArray($('tbody tr', table));
      var tableRows = $.grep(possibleTableRows, function(value, index) {
        return ($.inArray(value, defaults.ignoreRows) == -1);
      }, false)
      
      var numRows = tableRows.length
      var totalPages = resetTotalPages();
      var currPageNumber = (defaults.currPage > totalPages) ? 1 : defaults.currPage;
      if ($.inArray(defaults.rowsPerPage, defaults.optionsForRows) == -1)
        defaults.optionsForRows.push(defaults.rowsPerPage);
      
      
      function hideOtherPages(pageNum) {
        if (pageNum==0 || pageNum > totalPages)
          return;
        var startIndex = (pageNum - 1) * defaults.rowsPerPage;
        var endIndex = (startIndex + defaults.rowsPerPage - 1);
        $(tableRows).show();
        for (var i=0;i<tableRows.length;i++) {
          if (i < startIndex || i > endIndex) {
            $(tableRows[i]).hide()
          }
        }
      }
      
      function resetTotalPages() {
        var preTotalPages = Math.round(numRows / defaults.rowsPerPage);
        var totalPages = (preTotalPages * defaults.rowsPerPage < numRows) ? preTotalPages + 1 : preTotalPages;
        if ($(totalPagesId).length > 0)
          $(totalPagesId).html(totalPages);
        return totalPages;
      }
      
      function resetCurrentPage(currPageNum) {
        if (currPageNum < 1 || currPageNum > totalPages)
          return;
        currPageNumber = currPageNum;
        hideOtherPages(currPageNumber);
        $(currPageId).val(currPageNumber)
      }
      
      function resetPerPageValues() {
        var isRowsPerPageMatched = false;
        var optsPerPage = defaults.optionsForRows;
        optsPerPage.sort(function (a,b){return a - b;});
        var perPageDropdown = $(rowsPerPageId)[0];
        perPageDropdown.length = 0;
        for (var i=0;i<optsPerPage.length;i++) {
          if (optsPerPage[i] == defaults.rowsPerPage) {
            perPageDropdown.options[i] = new Option(optsPerPage[i], optsPerPage[i], true, true);
            isRowsPerPageMatched = true;
          }
          else {
            perPageDropdown.options[i] = new Option(optsPerPage[i], optsPerPage[i]);
          }
        }
        if (!isRowsPerPageMatched) {
          defaults.optionsForRows == optsPerPage[0];
        }
      }
      
      function createPaginationElements() {
        var htmlBuffer = [];
        htmlBuffer.push("<div id='tablePagination'>");
        htmlBuffer.push("<span id='tablePagination_perPage'>");
        htmlBuffer.push("<select id='tablePagination_rowsPerPage'><option value='5'>5</option></select>");
        htmlBuffer.push("per page");
        htmlBuffer.push("</span>");
        htmlBuffer.push("<span id='tablePagination_paginater'>");
        htmlBuffer.push("<img id='tablePagination_firstPage' src='"+defaults.firstArrow+"'>");
        htmlBuffer.push("<img id='tablePagination_prevPage' src='"+defaults.prevArrow+"'>");
        htmlBuffer.push("Page");
        htmlBuffer.push("<input id='tablePagination_currPage' type='input' value='"+currPageNumber+"' size='1'>");
        htmlBuffer.push("of <span id='tablePagination_totalPages'>"+totalPages+"</span>");
        htmlBuffer.push("<img id='tablePagination_nextPage' src='"+defaults.nextArrow+"'>");
        htmlBuffer.push("<img id='tablePagination_lastPage' src='"+defaults.lastArrow+"'>");
        htmlBuffer.push("</span>");
        htmlBuffer.push("</div>");
        return htmlBuffer.join("").toString();
      }
      
      if ($(totalPagesId).length == 0) {
        $(this).after(createPaginationElements());
      }
      else {
        $('#tablePagination_currPage').val(currPageNumber);
      }
      resetPerPageValues();
      hideOtherPages(currPageNumber);
      
      $(firstPageId).bind('click', function (e) {
        resetCurrentPage(1)
      });
      
      $(prevPageId).bind('click', function (e) {
        resetCurrentPage(currPageNumber - 1)
      });
      
      $(nextPageId).bind('click', function (e) {
        resetCurrentPage(currPageNumber + 1)
      });
      
      $(lastPageId).bind('click', function (e) {
        resetCurrentPage(totalPages)
      });
      
      $(currPageId).bind('change', function (e) {
        resetCurrentPage(this.value)
      });
      
      $(rowsPerPageId).bind('change', function (e) {
        defaults.rowsPerPage = parseInt(this.value, 10);
        totalPages = resetTotalPages();
        resetCurrentPage(1)
      });
      
		})
	};		
})(jQuery);