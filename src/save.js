import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
	const { file, text, displayMode, buttonWidthMode = 'auto', buttonWidthCustom = '', buttonAlign = 'left' } = attributes;
	const blockProps = useBlockProps.save();

	let buttonWidthStyle = {};
	if (buttonWidthMode === 'full') {
		buttonWidthStyle = { display: 'block', width: '100%' };
	} else if (buttonWidthMode === 'auto') {
		buttonWidthStyle = { display: 'inline-block' };
	} else if (buttonWidthMode === 'custom' && buttonWidthCustom) {
		buttonWidthStyle = { width: buttonWidthCustom, display: 'block' };
	}
	// アラインメント
	if (buttonWidthMode !== 'auto') {
		if (buttonAlign === 'center') {
			buttonWidthStyle.marginLeft = 'auto';
			buttonWidthStyle.marginRight = 'auto';
		} else if (buttonAlign === 'right') {
			buttonWidthStyle.marginLeft = 'auto';
			buttonWidthStyle.marginRight = 0;
		} else if (buttonAlign === 'left') {
			buttonWidthStyle.marginLeft = 0;
			buttonWidthStyle.marginRight = 'auto';
		}
	}

	let alignStyle = {};
	if (buttonAlign === 'center') {
		alignStyle = { textAlign: 'center' };
	} else if (buttonAlign === 'right') {
		alignStyle = { textAlign: 'right' };
	}

	// ファイルが指定されていない場合はエラーメッセージを表示
	if (!file) {
		return (
			<div {...blockProps}>
				<div className="bf-error">ファイルパスが指定されていません。</div>
			</div>
		);
	}

	const mergedClassName = [blockProps.className, 'bf-download-container'].filter(Boolean).join(' ');

	return (
		<div style={alignStyle}>
			<div
				{...blockProps}
				className={mergedClassName}
				style={{ ...blockProps.style, ...buttonWidthStyle }}
				data-file-path={file}
				data-display-mode={displayMode}
			>
				{text || 'ダウンロード'}
			</div>
		</div>
	);
}