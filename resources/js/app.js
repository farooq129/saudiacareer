import Alpine from 'alpinejs';

/*
| Alpine drives the small pieces of client state the board needs — the two
| header panels, the mobile drawer, and the dialogs on a listing. The pages
| themselves are server-rendered Blade; nothing here owns data.
|
| One component on the app root rather than several: the header panels and the
| dialogs have to know about each other (opening one closes the others, Escape
| closes whatever is showing), and a single piece of state is the honest way to
| say that.
*/

Alpine.data('board', () => ({
    mega: false,
    ai: false,
    drawer: false,
    contact: false,
    report: false,
    apply: false,

    init() {
        // Escape closes whatever is open, innermost first: a dialog over the
        // drawer should not close both at once.
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;

            if (this.contact || this.report || this.apply) {
                this.contact = false;
                this.report = false;
                this.apply = false;
            } else if (this.drawer) {
                this.drawer = false;
            } else if (this.mega || this.ai) {
                this.mega = false;
                this.ai = false;
            }
        });

        // A dialog or the drawer holds the page still behind it, so the page
        // does not scroll away under an open panel on a phone. The class goes
        // on <body> because that is the element that actually scrolls.
        this.$watch('locked', (locked) => {
            document.body.classList.toggle('is-locked', locked);
        });
    },

    get locked() {
        return this.drawer || this.contact || this.report || this.apply;
    },
}));

window.Alpine = Alpine;

Alpine.start();
