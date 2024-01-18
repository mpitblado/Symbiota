const reorderElements = (parentDivId, desiredDivIds, removeDivIds) => {
  const parent = document.getElementById(parentDivId);
  const allChildren = Array.from(parent.children);
  const allChildrenIds = allChildren.map((child) => child.id);
  const revisedDesired = desiredDivIds.filter((desiredDiv) => {
    return (
      allChildrenIds.includes(desiredDiv) ||
      desiredDiv === "br" ||
      desiredDiv == "hr"
    );
  });
  allChildren.forEach((childEl) => {
    const currentId = childEl.id;
    if (revisedDesired.includes(currentId)) {
      currentChildIdxInDesiredList = revisedDesired.indexOf(currentId);
      parent.appendChild(childEl);
      if (revisedDesired[currentChildIdxInDesiredList + 1] === "hr") {
        const hrElement = document.createElement("hr");
        hrElement.style.cssText = "margin-bottom: 2rem; clear: both;";
        parent.appendChild(hrElement);
      }
      if (revisedDesired[currentChildIdxInDesiredList + 1] === "br") {
        const hrElement = document.createElement("br");
        hrElement.style.cssText = "margin-bottom: 2rem; clear: both;";
        parent.appendChild(hrElement);
      }
    }
    if (removeDivIds.includes(currentId)) {
      childEl.remove();
    }
  });
};

// Example implementation below. Add the following code (or something like it with the desired order of divs) the end of collections/individual/index.php

{
  /* <script type="text/javascript">
		document.addEventListener('DOMContentLoaded', ()=>{
			reorderElements("occur-div", ["cat-div", "hr", "sciname-div", "family-div","hr", "taxonremarks-div", "assoccatnum-div", "assoccatnum-div", "idqualifier-div","identref-div","identremarks-div", "determination-div", "hr", "identby-div", "identdate-div","verbeventid-div", "hr", "recordedby-div", "recordnumber-div", "record-id-div", "eventdate-div", "hr", "locality-div", "latlng-div", "verbcoord-div", "elev-div", "habitat-div", "assoctaxa-div", "attr-div", "notes-div", "hr", "rights-div", "contact-div", "openeditor-div"], ["occurrenceid-div", "disposition-div"]);

		});
	</script> */
}
