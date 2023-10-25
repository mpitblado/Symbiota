$(document).ready(function () {
  //Start : Menu dropdown close from outside

  $(document).on("click", function (event) {
    if (
      event.target.className == "usa-accordion-button usa-nav-link" ||
      event.target.id == "menuName"
    ) {
      //No action needed as already implemented
    } else {
      var buttonElements = $("ul > li > button");
      for (var i = 1; i <= buttonElements.length; i++) {
        var element = buttonElements.eq(i);
        $("#btnMenu-" + i).attr("aria-expanded", "false");
        $("#side-nav-" + i).attr("aria-hidden", "true");
      }
    }
  });
  //End : Menu dropdown close from outside

  /* Start :OC Image Gallery : Move 640 pixels link under 300 dpi download button */
  // i.e. https://www.ars.usda.gov/oc/images/photos/jan01/k9179-1
  if (
    window.location.href.indexOf("/oc/images/photos/") > -1 &&
    $('a img[src*="downloadbutton.gif"]').length > 0
  ) {
    // move the 640 pixels link : begin
    $("pre:contains(640 pixels wide:)").insertAfter(
      $('a img[src*="downloadbutton.gif"]').parent()
    ); //Remove the 640 pixels wide - link and Insert after the download button

    $(".usa-layout-docs-main_content")
      .find("p:first") //Find the first <p> tag under div .usa-layout-docs-main_content
      .attr("align", "RIGHT"); //Add attribute to do right align

    var source = '<pre style="display: none;">'; // For IE browser only accept this format display: none;
    $('p[align="RIGHT"]').html(function () {
      return $(this).html().replace(source, "<br>"); //Replace the <pre> tag
    });
    // move the 640 pixels link : end

    // move the 300 dpi download button : begin
    var href = $(".usa-layout-docs-main_content")
      .find("p:first")
      .children()
      .attr("href"); //To get href url attribute value
    var img = href.substring(href.lastIndexOf("/") + 1); //To get Image Name (i.e. k9719-1.jpg)
    img = "300 dpi: (<a href='" + href + "' target='_blank' >" + img + "</a>)";
    $(".usa-layout-docs-main_content").find("p:first").prepend(img);
    $('a img[src*="downloadbutton.gif"]').parent().remove();
    // move the 300 dpi button : end
  }
  /* End :OC Image Gallery : Move 640 pixels link under 300 dpi download button */

  /* Start : Remove News Header/Footer  : Stories for the years of 2001-2004 */
  /* i.e. https://www.ars.usda.gov/news-events/news/research-news/2003/ars-releases-new-blackberry-cultivars/ */
  if (
    window.location.href.indexOf("/news-events/news/research-news/200") > -1
  ) {
    $('img[src*="/ARSUserFiles/oc/graphics/newson.gif"]')
      .parentsUntil("table")
      .css("display", "none");
    $('img[src*="/ARSUserFiles/oc/graphics/bottomenu.gif"]')
      .parentsUntil("table")
      .css("display", "none");
  }
  /* End : Remove News Header/Footer  : Stories for the years of 2001-2004 */

  /*start Fixed Mobile PDF page number for all the page common jquery code */
  if (isMobile.phone) {
    var $anchor = $("a");
    $anchor.each(function () {
      var href = $(this).attr("href");
      if (href) {
        var lowerHref = href.toLowerCase();
        if (lowerHref.indexOf(".pdf#page=") > -1) {
          var newhref = lowerHref.replace(".pdf#page=", ".pdf#page");
          $(this).attr("href", newhref);
        }
      }
    });
  }
  /*End   Fixed Mobile PDF page number for all the page common jquery code */
});

/*Start : Floating Social icons */
$(window).scroll(function () {
  $(".float-ConnectwithARS").css("top", Math.max(0, 620 - $(this).scrollTop()));
  if ($(this).scrollTop() > 1495) {
    $(".float-ConnectwithARS").css({ top: "-620px" });
  }
});
$(window).on("resize", function () {
  windowsize = $(window).height();
  if (windowsize > 890) {
    $(".float-ConnectwithARS").css({ top: "620px" });
  } else {
    $(".float-ConnectwithARS").css({ top: "620px" });
  }
});
/*End : Floating Social icons */
