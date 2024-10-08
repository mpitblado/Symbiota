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
    showOnlyRelevantFields(selectedValue);
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
  showOnlyRelevantFields(selectedValue);
  //   console.log("deleteMe got here b and selectedValue is: ");
  //   console.log(selectedValue);
  //   if (selectedValue >= 220) {
  //     toggle("div2hide");
  //     toggle("div3hide");
  //   }
  //   if (selectedValue === 300) {
  //     toggle("div2hide");
  //     toggle("div3hide");
  //     toggle("unit4Display");
  //     toggle("unit5Display");
  //   }
}

function showOnlyRelevantFields(rankId) {
  console.log("deleteMe rankId is: ");
  console.log(rankId);
  //   $rankId = rankId;
  const label = document.getElementById("unitind1label");
  const unitind1Select = document.getElementById("unitind1-select");
  const div2Hide = document.getElementById("div2hide");
  const div3Hide = document.getElementById("div3hide");
  const div4Hide = document.getElementById("div4hide");
  const div5Hide = document.getElementById("div5hide");
  const div4Display = document.getElementById("unit4Display");
  const div5Display = document.getElementById("unit5Display");
  const authorDiv = document.getElementById("author-div");
  const parentNode = div5Hide.parentNode; // @TODO confirm
  const genusDiv = document.getElementById("genus-div");

  rankIdsToHideUnit2From = {
    "non-ranked node": 0,
    organism: 1,
    kingdom: 10,
    subkingdom: 20,
    division: 30,
    subdivision: 40,
    superclass: 50,
    class: 60,
    subclass: 70,
    order: 100,
    suborder: 110,
    family: 140,
    subfamily: 150,
    tribe: 160,
    subtribe: 170,
    genus: 180,
    section: 200,
    subsection: 210,
  }; // not 190 (subgenera)
  rankIdsToHideUnit3From = { ...rankIdsToHideUnit2From, genus: 190 };
  rankIdsToHideUnit4From = {
    ...rankIdsToHideUnit3From,
    species: 220,
    subspecies: 230,
    variety: 240,
    subvariety: 250,
    form: 260,
    subform: 270,
  };
  rankIdsToHideUnit5From = { ...rankIdsToHideUnit4From };

  allRankIds = { rankIdsToHideUnit5From, cultivar: 300 };

  if (Object.values(rankIdsToHideUnit2From).includes(rankId)) {
    div2Hide.style.display = "none";
  } else {
    div2Hide.style.display = "block";
  }

  if (Object.values(rankIdsToHideUnit3From).includes(rankId)) {
    div3Hide.style.display = "none";
  } else {
    div3Hide.style.display = "block";
  }

  // if (rankId > 150) {
  //   // @TODO do we want unit2 and unit3 to appear upon Tribe selection?? Why not 220 here??
  //   div2Hide.style.display = "block";
  //   div3Hide.style.display = "block";
  // } else {
  //   div2Hide.style.display = "none";
  //   div3Hide.style.display = "none";
  // }

  if (rankId <= rankIdsToHideUnit2From.genus) {
    // Get the name of selected option
    const rankIdSelector = document.getElementById("rankid");
    // const selectedOption = this.options[this.selectedIndex];
    const selectedOption = rankIdSelector.options[rankId];
    const selectedOptionText = selectedOption.textContent.trim();

    // Set the label for "UnitName1" based on the selected option text
    label.textContent = selectedOptionText + " Name";
  } else {
    label.textContent = "Genus Name";
  }

  // if (rankId < allRankIds.genus) {
  if (Object.values(rankIdsToHideUnit2From).includes(rankId)) {
    console.log("deleteMe got here c1");
    unitind1Select.style.display = "none";
  } else {
    console.log("deleteMe got here c2");
    unitind1Select.style.display = "inline-block";
  }

  if (
    Object.values(rankIdsToHideUnit5From).includes(rankId)
    //   < rankIdsToHideUnit4From.species
  ) {
    console.log("deleteMe got here b1");
    //set the unit 2-5 values to '', because this taxon rank doesn't have them
    document.getElementById("unitname2").value = null;
    document.getElementById("unitind2").value = null;
    document.getElementById("unitind3").value = null;
    document.getElementById("div4-input").value = null;
    document.getElementById("div5-input").value = null;
  }
  // if (rankId < 300) {
  if (Object.values(rankIdsToHideUnit5From).includes(rankId)) {
    console.log("deleteMe got here b1");
    //set the unit 4-5 values to '', because this taxon rank doesn't have them
    document.getElementById("div4-input").value = null;
    document.getElementById("div5-input").value = null;
  }

  // if (rankId >= 220) {
  //   toggle("div2hide");
  //   div4Display.style.display = "inline-block";
  //   toggle("unit3Display");
  // }

  console.log("deleteMe got here x and rankId is: ");
  console.log(rankId);

  if (rankId == allRankIds.cultivar) {
    console.log("got here d2");
    div4Display.style.display = "inline-block";
    div5Display.style.display = "inline-block";
    div4Hide.style.display = "block";
    div5Hide.style.display = "block";
    parentNode.insertBefore(authorDiv, div5Hide);
  } else {
    console.log("got here d1");
    div4Hide.style.display = "none";
    div5Hide.style.display = "none";
    //   console.log("deleteMe div4Hide is: ");
    //   console.log(div4Hide);
    document.getElementById("div4-input").value = null;
    document.getElementById("div5-input").value = null; // @TODO
    parentNode.insertBefore(authorDiv, genusDiv);
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
