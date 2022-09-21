/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';

    config.fillEmptyBlocks      = false;
    config.fullPage             = false;
    config.ignoreEmptyParagraph = true;
    config.enterMode            = CKEDITOR.ENTER_BR;
    config.entities             = false;
    config.basicEntities        = false;
    config.allowedContent       = true;
    config.extraPlugins         = 'wordcount,notification';
    config.wordcount            = {
        showParagraphs    : false,
        showWordCount     : false,
        showCharCount     : true,
        maxCharCount      : -1,
        countSpacesAsChars: true,
    };
};
