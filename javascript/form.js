function transferValue(from, to) {
    var from=document.getElementById(from);
    document.getElementById(to).value = from.options[from.selectedIndex].text;
}