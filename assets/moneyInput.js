$(window).load(function () {

	$('input.money-input').on('input', function () {
		this.value = this.value.replace(/ /g, '');
		this.value = this.value.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
	});

});
