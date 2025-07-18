import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	RichText,
	BlockControls,
	AlignmentToolbar,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,

	Notice,
	Button,
	Modal,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,

} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

export default function Edit({ attributes, setAttributes }) {
	const { file, text, displayMode, buttonWidthMode = 'auto', buttonWidthCustom = '', buttonAlign = 'left' } = attributes;
	const [fileError, setFileError] = useState('');
	const [isFileBrowserOpen, setIsFileBrowserOpen] = useState(false);
	const [browserFiles, setBrowserFiles] = useState([]);
	const [currentPath, setCurrentPath] = useState('');
	const [isLoading, setIsLoading] = useState(false);

	const blockProps = useBlockProps();
	const { style, ...outerProps } = blockProps;

	// ファイルパスのバリデーション
	useEffect(() => {
		if (file) {
			if (file.includes('..')) {
				setFileError(__('無効なファイルパス: ".." は使用できません', 'bf-basic-guard'));
			} else {
				setFileError('');
			}
		} else {
			setFileError('');
		}
	}, [file]);

	const onChangeFile = (newFile) => {
		setAttributes({ file: newFile });
	};

	const onChangeText = (newText) => {
		setAttributes({ text: newText });
	};

	const onChangeDisplayMode = (newMode) => {
		setAttributes({ displayMode: newMode });
	};

	const onChangeButtonWidthMode = (value) => {
		setAttributes({ buttonWidthMode: value });
	};
	const onChangeButtonWidthCustom = (value) => {
		setAttributes({ buttonWidthCustom: value });
	};

	const onChangeButtonAlign = (value) => {
		if (value === undefined || value === null) return;
		setAttributes({ buttonAlign: value });
	};

	// ボタン幅のstyleを決定
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

	// アラインメント用スタイル
	let alignStyle = {};
	if (buttonAlign === 'center') {
		alignStyle = { textAlign: 'center' };
	} else if (buttonAlign === 'right') {
		alignStyle = { textAlign: 'right' };
	}

	// ファイルブラウザーを開く
	const openFileBrowser = () => {
		setIsFileBrowserOpen(true);
		loadFiles('');
	};

	// ファイル一覧を取得
	const loadFiles = async (path) => {
		setIsLoading(true);
		setCurrentPath(path);

		try {
			// 既存のAJAXエンドポイントを使用
			const formData = new FormData();
			formData.append('action', 'bf_basic_guard_browse_files');
			formData.append('path', path);
			formData.append('page', '1');
			formData.append('nonce', window.bfBasicGuardEditor?.nonce || '');

			const response = await fetch(window.bfBasicGuardEditor?.ajaxUrl || window.ajaxurl, {
				method: 'POST',
				body: formData
			});

			const result = await response.json();

			if (result.success) {
				setBrowserFiles(result.data.items || []);
			} else {
				setBrowserFiles([]);
				console.error('ファイル取得エラー:', result.data);
			}
		} catch (error) {
			console.error('ファイル取得エラー:', error);
			setBrowserFiles([]);
		} finally {
			setIsLoading(false);
		}
	};

	// ディレクトリを開く
	const openDirectory = (path) => {
		loadFiles(path);
	};

	// 上の階層に移動
	const goUpDirectory = () => {
		if (currentPath) {
			const parentPath = currentPath.split('/').slice(0, -1).join('/');
			loadFiles(parentPath);
		}
	};

	// ファイルを選択
	const selectFile = (filePath) => {
		setAttributes({ file: filePath });
		setIsFileBrowserOpen(false);
	};

	// プレビュー表示

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('ダウンロード設定', 'bf-basic-guard')}>
					<div style={{ marginBottom: '12px' }}>
						<label style={{ display: 'block', marginBottom: '6px', fontWeight: '600' }}>
							{__('ファイルパス', 'bf-basic-guard')}
						</label>
						<div style={{ display: 'flex', gap: '8px' }}>
							<TextControl
								value={file}
								onChange={onChangeFile}
								placeholder={__('例: /path/to/file.pdf', 'bf-basic-guard')}
								style={{ flex: 1 }}
							/>
							<Button
								variant="secondary"
								onClick={openFileBrowser}
								style={{ flexShrink: 0 }}
							>
								{__('選択', 'bf-basic-guard')}
							</Button>
						</div>
					</div>
					{fileError && (
						<Notice status="error" isDismissible={false}>
							{fileError}
						</Notice>
					)}
					<TextControl
						label={__('表示テキスト', 'bf-basic-guard')}
						value={text}
						onChange={onChangeText}
					/>
					<SelectControl
						label={__('表示モード', 'bf-basic-guard')}
						value={displayMode}
						options={[
							{ label: 'ダウンロード', value: 'download' },
							{ label: 'ブラウザ表示', value: 'display' }
						]}
						onChange={onChangeDisplayMode}
					/>
					<SelectControl
						label={__('ボタン幅', 'bf-basic-guard')}
						value={buttonWidthMode}
						onChange={onChangeButtonWidthMode}
						options={[
							{ label: __('全幅', 'bf-basic-guard'), value: 'full' },
							{ label: __('ラベルに応じる', 'bf-basic-guard'), value: 'auto' },
							{ label: __('カスタム', 'bf-basic-guard'), value: 'custom' },
						]}
					/>
					{buttonWidthMode === 'custom' && (
						<TextControl
							label={__('カスタム幅（例: 200px, 50% など）', 'bf-basic-guard')}
							value={buttonWidthCustom}
							onChange={onChangeButtonWidthCustom}
							placeholder="200px"
						/>
					)}
				</PanelBody>
			</InspectorControls>
			<BlockControls>
				<AlignmentToolbar
					value={buttonAlign}
					onChange={(value) => setAttributes({ buttonAlign: value || 'left' })}
					alignmentControls={[
						{ icon: 'editor-alignleft', title: __('左', 'bf-basic-guard'), align: 'left' },
						{ icon: 'editor-aligncenter', title: __('中央', 'bf-basic-guard'), align: 'center' },
						{ icon: 'editor-alignright', title: __('右', 'bf-basic-guard'), align: 'right' },
					]}
				/>
			</BlockControls>
			<div {...outerProps} style={{ ...outerProps.style, ...alignStyle }}>
				<div
					className="bf-download-container"
					style={{ ...style, ...buttonWidthStyle }}
				>
					{text || __('ダウンロード', 'bf-basic-guard')}
				</div>
			</div>

			{isFileBrowserOpen && (
				<Modal
					title={__('ファイルを選択', 'bf-basic-guard')}
					onRequestClose={() => setIsFileBrowserOpen(false)}
					style={{ maxWidth: '800px', width: '90vw' }}
				>
					<div style={{ padding: '16px' }}>
						{/* パス表示と上の階層ボタン */}
						<div style={{ marginBottom: '16px', padding: '12px', backgroundColor: '#f1f1f1', borderRadius: '4px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
							<div>
								<strong>{__('現在のディレクトリ:', 'bf-basic-guard')}</strong>
								<code style={{ marginLeft: '8px' }}>
									{currentPath || __('ルートディレクトリ', 'bf-basic-guard')}
								</code>
							</div>
							{currentPath && (
								<Button variant="secondary" onClick={goUpDirectory}>
									{__('上の階層へ', 'bf-basic-guard')}
								</Button>
							)}
						</div>

						{/* ローディング表示 */}
						{isLoading && (
							<div style={{ textAlign: 'center', padding: '20px' }}>
								{__('読み込み中...', 'bf-basic-guard')}
							</div>
						)}

						{/* ファイル一覧 */}
						{!isLoading && (
							<div style={{ maxHeight: '400px', overflowY: 'auto', border: '1px solid #ddd', borderRadius: '4px' }}>
								{browserFiles.length === 0 ? (
									<div style={{ padding: '20px', textAlign: 'center', color: '#666' }}>
										{__('ファイルまたはディレクトリが見つかりませんでした。', 'bf-basic-guard')}
									</div>
								) : (
									<table style={{ width: '100%', borderCollapse: 'collapse' }}>
										<thead>
											<tr style={{ backgroundColor: '#f9f9f9' }}>
												<th style={{ padding: '12px', textAlign: 'left', borderBottom: '1px solid #ddd' }}>
													{__('ファイル名', 'bf-basic-guard')}
												</th>
												<th style={{ padding: '12px', textAlign: 'left', borderBottom: '1px solid #ddd', width: '100px' }}>
													{__('タイプ', 'bf-basic-guard')}
												</th>
												<th style={{ padding: '12px', textAlign: 'center', borderBottom: '1px solid #ddd', width: '80px' }}>
													{__('操作', 'bf-basic-guard')}
												</th>
											</tr>
										</thead>
										<tbody>
											{browserFiles.map((item, index) => (
												<tr key={index} style={{ borderBottom: '1px solid #eee' }}>
													<td style={{ padding: '12px' }}>
														<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
															<span style={{ fontSize: '16px' }}>
																{item.type === 'directory' ? '📁' : '📄'}
															</span>
															<span style={{ fontWeight: item.type === 'directory' ? '600' : 'normal' }}>
																{item.name}
															</span>
														</div>
													</td>
													<td style={{ padding: '12px', color: '#666' }}>
														{item.type === 'directory'
															? __('ディレクトリ', 'bf-basic-guard')
															: __('ファイル', 'bf-basic-guard')
														}
													</td>
													<td style={{ padding: '12px', textAlign: 'center' }}>
														{item.type === 'directory' ? (
															<Button
																variant="secondary"
																size="small"
																onClick={() => openDirectory(item.path)}
															>
																{__('開く', 'bf-basic-guard')}
															</Button>
														) : (
															<Button
																variant="primary"
																size="small"
																onClick={() => selectFile(item.path)}
															>
																{__('選択', 'bf-basic-guard')}
															</Button>
														)}
													</td>
												</tr>
											))}
										</tbody>
									</table>
								)}
							</div>
						)}

						{/* フッターボタン */}
						<div style={{ marginTop: '16px', display: 'flex', justifyContent: 'flex-end', gap: '8px' }}>
							<Button variant="secondary" onClick={() => setIsFileBrowserOpen(false)}>
								{__('キャンセル', 'bf-basic-guard')}
							</Button>
						</div>
					</div>
				</Modal>
			)}
		</>
	);
}