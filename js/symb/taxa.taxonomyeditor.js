$(document).ready(function () {
  $("#tabs").tabs({ active: tabIndex });

  $("#parentstr").autocomplete({
    source: function (request, response) {
      $.getJSON(
        "rpc/gettaxasuggest.php",
        {
          term: request.term,
          taid: document.taxauthidform.taxauthid.value,
          rhigh: document.taxoneditform.rankid.value,
        },
        response
      );
    },
    minLength: 3,
    autoFocus: true,
  });

  document.getElementById("rankid").addEventListener("change", function () {
    const selectedValue = Number(this.value); // Get the chosen value
    console.log("deleteMe selectedValue is: ");
    console.log(selectedValue);
    $rankId = selectedValue;
    const label = document.getElementById("unitind1label");
    const unitind1Select = document.getElementById("unitind1-select");
    const div2Hide = document.getElementById("div2hide");
    const div3Hide = document.getElementById("div3hide");
    const div4hide = document.getElementById("div4hide");
    const div5Hide = document.getElementById("div5hide");
    const div4Display = document.getElementById("unit4Display");
    const div5Display = document.getElementById("unit5Display");
    const authorDiv = document.getElementById("author-div");
    const parentNode = div5Hide.parentNode; // @TODO confirm
    const genusDiv = document.getElementById("genus-div");
    if (selectedValue > 150) {
      // @TODO do we want unit2 and unit3 to appear upon Tribe selection?? Why not 220 here??
      div2Hide.style.display = "block";
      div3Hide.style.display = "block";
    } else {
      div2Hide.style.display = "none";
      div3Hide.style.display = "none";
    }
    if (selectedValue <= 180) {
      // Get the name of selected option
      const selectedOption = this.options[this.selectedIndex];
      const selectedOptionText = selectedOption.textContent.trim();

      // Set the label for "UnitName1" based on the selected option text
      label.textContent = selectedOptionText + " Name";
    } else {
      label.textContent = "Genus Name";
    }

    if (selectedValue < 180) {
      unitind1Select.style.display = "none";
    } else {
      unitind1Select.style.display = "inline-block";
    }

    if (selectedValue < 220) {
      console.log("deleteMe got here b1");
      //set the unit 2-5 values to '', because this taxon rank doesn't have them
      document.getElementById("unitname2").value = null;
      document.getElementById("unitind2").value = null;
      document.getElementById("unitind3").value = null;
      document.getElementById("div4-input").value = null;
      document.getElementById("div5-input").value = null;
    }
    if (selectedValue < 300) {
      console.log("deleteMe got here b1");
      //set the unit 4-5 values to '', because this taxon rank doesn't have them
      document.getElementById("div4-input").value = null;
      document.getElementById("div5-input").value = null;
    }

    // if (selectedValue >= 220) {
    //   toggle("div2hide");
    //   div4Display.style.display = "inline-block";
    //   toggle("unit3Display");
    // }

    if (selectedValue == 300) {
      div4Display.style.display = "inline-block";
      div5Display.style.display = "inline-block";
      div4hide.style.display = "block";
      div5hide.style.display = "block";
      parentNode.insertBefore(authorDiv, div5hide);
    } else {
      div4hide.style.display = "none";
      div5hide.style.display = "none";
      console.log("deleteMe div4hide is: ");
      document.getElementById("div5-input").value = null;
      document.getElementById("div5-input").value = null; // @TODO
      console.log(div4hide);
      parentNode.insertBefore(authorDiv, genusDiv);
    }
  });

  $("#aefacceptedstr").autocomplete({
    source: "rpc/getacceptedsuggest.php",
    dataType: "json",
    minLength: 3,
    autoFocus: true,
    change: function (event, ui) {
      if (ui.item == null && this.value.trim() != "") {
        alert(
          "Name must be selected from list of accepted taxa currently in the system."
        );
        this.focus();
        this.form.tidaccepted.value = "";
      }
    },
    focus: function (event, ui) {
      this.form.tidaccepted.value = ui.item.id;
    },
    select: function (event, ui) {
      this.form.tidaccepted.value = ui.item.id;
    },
  });

  $("#ctnafacceptedstr").autocomplete({
    source: "rpc/getacceptedsuggest.php",
    dataType: "json",
    minLength: 3,
    autoFocus: true,
    change: function (event, ui) {
      if (ui.item == null && this.value.trim() != "") {
        alert(
          "Name must be selected from list of accepted taxa currently in the system."
        );
        this.focus();
        this.form.tidaccepted.value = "";
      }
    },
    focus: function (event, ui) {
      this.form.tidaccepted.value = ui.item.id;
    },
    select: function (event, ui) {
      this.form.tidaccepted.value = ui.item.id;
    },
  });
});

function toggleEditFields() {
  toggle("editfield");
  toggle("kingdomdiv");
  const selectedValue = Number(document.getElementById("rankid").value);
  console.log("deleteMe got here b and selectedValue is: ");
  console.log(selectedValue);
  if (selectedValue >= 220) {
    toggle("div2hide");
    toggle("div3hide");
  }
  if (selectedValue === 300) {
    toggle("div2hide");
    toggle("div3hide");
    toggle("unit4Display");
    toggle("unit5Display");
  }
}

function toggle(target) {
  var ele = document.getElementById(target);
  if (ele) {
    if (ele.style.display == "none") {
      ele.style.display = "";
    } else {
      ele.style.display = "none";
    }
  } else {
    var divs = document.getElementsByTagName("div");
    var i;
    for (i = 0; i < divs.length; i++) {
      var divObj = divs[i];
      if (divObj.className == target) {
        if (divObj.style.display == "none") {
          divObj.style.display = "block";
        } else {
          divObj.style.display = "none";
        }
      }
    }

    var spans = document.getElementsByTagName("span");
    var j;
    for (j = 0; j < spans.length; j++) {
      var spanObj = spans[j];
      if (spanObj.className == target) {
        if (spanObj.style.display == "none") {
          spanObj.style.display = "inline";
        } else {
          spanObj.style.display = "none";
        }
      }
    }
  }
}

function validateTaxonEditForm(f) {
  if (f.unitname1.value.trim() == "") {
    alert("Unitname 1 field must have a value");
    return false;
  }
  return true;
}

function verifyChangeToNotAcceptedForm(f) {
  if (f.acceptedstr.value == "") {
    alert("Please enter an accepted name to which this taxon will be linked!");
    return false;
  } else if (f.tidaccepted.value == "" || f.tidaccepted.value == "undefined") {
    alert(
      "Please select a name from the list. If name is not in the list, target taxon is not listed as accepted, or has not yet been entered in thesarurus."
    );
    return false;
  }
  return true;
}

function verifyLinkToAcceptedForm(f) {
  if (f.acceptedstr.value == "") {
    alert("Please enter an accepted name to which this taxon will be linked!");
    return false;
  } else if (f.tidaccepted.value == "" || f.tidaccepted.value == "undefined") {
    alert(
      "Taxon entered appears not to be in thesaurus or is not listed as an accepted taxon. Name must be selected from list."
    );
    return false;
  }
  return true;
}

function submitTaxStatusForm(f) {
  $.ajax({
    type: "POST",
    url: "rpc/gettid.php",
    data: { sciname: f.parentstr.value },
  }).done(function (msg) {
    if (msg == 0) {
      alert(
        "ERROR: Parent taxon not found in thesaurus. It is either misspelled or needs to be added to the thesaurus."
      );
    } else {
      f.parenttid.value = msg;
      f.submit();
    }
  });
}
