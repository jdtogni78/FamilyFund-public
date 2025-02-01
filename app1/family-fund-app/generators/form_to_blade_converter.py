import re
import os
import sys
from pathlib import Path

def convert_form_open(line):
    # Match Form::open with route
    route_pattern = r"{!! Form::open\(\['route'\s*=>\s*'([^']+)'.*?\]\) !!}"
    route_match = re.search(route_pattern, line)
    if route_match:
        route_name = route_match.group(1)
        return f'<form method="POST" action="{{{{ route(\'{route_name}\') }}}}">\n@csrf'
    
    # Match Form::open with url
    url_pattern = r"{!! Form::open\(\['url'\s*=>\s*'([^']+)'.*?\]\) !!}"
    url_match = re.search(url_pattern, line)
    if url_match:
        url = url_match.group(1)
        return f'<form method="POST" action="{{{{ url(\'{url}\') }}}}">\n@csrf'
    
    # {!! Form::open(['route' => ['assets.destroy', $asset->id], 'method' => 'delete']) !!}
    url_pattern = r"{!! Form::open\(\['route'\s*=>\s*'([^\]]+), 'method'\s*=>\s*'([^']+)'\]\) !!}"
    url_match = re.search(url_pattern, line)
    if url_match:
        route = url_match.group(1)
        method = url_match.group(2)
        return f'<form method="{method}" action="{{{{ route(\'{route}\') }}}}">\n@csrf'
    
    return line

def convert_form_input(line):
    # Convert Form::text
    text_pattern = r"{!! Form::text\('([^']+)',\s*(\$[^,]+)?,\s*\[(.*?)\]\) !!}"
    text_match = re.search(text_pattern, line)
    if text_match:
        name = text_match.group(1)
        value = text_match.group(2) or ''
        attrs = text_match.group(3)
        
        # Convert attributes from Laravel to HTML
        attrs_dict = {}
        if attrs:
            attrs_pairs = re.findall(r"'([^']+)'\s*=>\s*'([^']+)'", attrs)
            attrs_dict = {k: v for k, v in attrs_pairs}
        
        attrs_str = ' '.join([f'{k}="{v}"' for k, v in attrs_dict.items()])
        value_str = f' value="{{{{ {value} }}}}" ' if value and value.startswith('$') else ''
        
        return f'<input type="text" name="{name}"{value_str} {attrs_str}>'
    
    return line

def convert_form_select(line):
    # Convert Form::select
    select_pattern = r"{!! Form::select\(([^,]+),\s*([^,]+),\s*([^,]+),\s*(.*?)\);?\s*!!}"
    select_match = re.search(select_pattern, line)
    if select_match:
        name = convert_var(select_match.group(1))
        options = convert_var(select_match.group(2))
        selected = convert_var(select_match.group(3)) or ''
        attrs = select_match.group(4)
        
        # Convert attributes
        attrs_dict = {}
        if attrs:
            attrs_pairs = re.findall(r"([^']+)'\s*=>\s*'?([^',]+)", attrs)
            attrs_dict = {k: v for k, v in attrs_pairs}
        
        attrs_str = ' '.join([f'{k}="{v}"' for k, v in attrs_dict.items()])
        
        select_html = f'<select name="{name}" {attrs_str}>\n'
        select_html += f'    @foreach({options} as $value => $label)\n'
        select_html += f'        <option value="{{{{ $value }}}}" {{{{ {selected} == $value ? \'selected\' : \'\' }}}}>{{{{ $label }}}}</option>\n'
        select_html += f'    @endforeach\n'
        select_html += '</select>'
        
        return select_html
    
    return line

def convert_form_close(line):
    if '{!! Form::close() !!}' in line:
        return '</form>'
    return line

def convert_var(var):
    if var == 'null':
        return ''
    if var.startswith("'") and var.endswith("'"):
        return var[1:-1]
    if var.startswith('$'):
        return '{{{{ ' + var + ' }}}}'
    return var

def convert_form_label(line):
    if '{!! Form::label' in line:
        label_pattern = r"{!! Form::label\('?([^,']+)'?,\s*('[^']+')\) !!}"
        label_match = re.search(label_pattern, line)
        if label_match:
            field = convert_var(label_match.group(1))
            label = convert_var(label_match.group(2))
            return f'<label for="{field}">{label}</label>'
    return line

#     {!! Form::text('source', null, ['class' => 'form-control','maxlength' => 30]) !!}
# <input type="text" name="source" class="form-control" maxlength="30">
def convert_form_text(line):
    if '{!! Form::text' in line:
        text_pattern = r"{!! Form::text\('?([^',]+)'?,\s*([^,]+),\s*(\[.*?\]|.*?)\) !!}"
        text_match = re.search(text_pattern, line)
        if text_match:
            name = convert_var(text_match.group(1))
            value = convert_var(text_match.group(2))
            attrs = text_match.group(3)
            # transform classes to html
            attrs_dict = {k: v for k, v in re.findall(r"([^']+)'\s*=>\s*'?([^',]+)", attrs)}
            attrs_str = ' '.join([f'{k}="{v}"' for k, v in attrs_dict.items()])
            return f'<input type="text" name="{name}" value="{value}" {attrs_str}>'
    return line

#    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
#    <button type="submit" class="btn btn-primary">Save</button>
def convert_form_submit(line):
    if '{!! Form::submit' in line:
        submit_pattern = r"{!! Form::submit\('([^']+)',\s*\[(.*?)\]\) !!}"
        submit_match = re.search(submit_pattern, line)
        if submit_match:
            value = submit_match.group(1)
            attrs = submit_match.group(2)
            attrs_dict = {k: v for k, v in re.findall(r"([^']+)'\s*=>\s*'?([^',]+)", attrs)}
            attrs_str = ' '.join([f'{k}="{v}"' for k, v in attrs_dict.items()])
            return f'<button type="submit" {attrs_str}>{value}</button>'
    return line

def convert_form_number(line):
    if '{!! Form::number' in line:
        number_pattern = r"{!! Form::number\(([^,]+),\s*([^,]+),\s*(\[.*?\]|.*?)\) !!}"
        number_match = re.search(number_pattern, line)
        if number_match:
            name = convert_var(number_match.group(1))
            value = convert_var(number_match.group(2))
            attrs = number_match.group(3)
            attrs_dict = {k: v for k, v in re.findall(r"([^']+)'\s*=>\s*'?([^',]+)", attrs)}
            attrs_str = ' '.join([f'{k}="{v}"' for k, v in attrs_dict.items()])
            return f'<input type="number" name="{name}" value="{value}" {attrs_str}>'
    return line

def convert_form_hidden(line):
    if '{!! Form::hidden' in line:
        hidden_pattern = r"{!! Form::hidden\(([^,]+),\s*([^,]+),?\s*(\[.*?\]|.*?)\);?\s*!!}"
        hidden_match = re.search(hidden_pattern, line)
        if hidden_match:
            name = convert_var(hidden_match.group(1))
            value = convert_var(hidden_match.group(2))
            attrs = hidden_match.group(3)
            attrs_dict = {k: v for k, v in re.findall(r"([^']+)'\s*=>\s*'?([^',]+)", attrs)}
            attrs_str = ' '.join([f'{k}="{v}"' for k, v in attrs_dict.items()])
            return f'<input type="hidden" name="{name}" value="{value}" {attrs_str}>'
    return line

# {!! Form::model($accountReport, ['route' => ['accountReports.update', $accountReport->id], 'method' => 'patch']) !!}
# <form method="patch" action="{{{{ route(\'accountReports.update\', [$accountReport->id]) }}}}" >
def convert_form_model(line):
    if '{!! Form::model' in line:
        model_pattern = r"{!! Form::model\(([^,]+),\s*(.*?),?\s*('method'\s*=>\s*'([^']+)')?\]\);?\s*!!}"
        model_match = re.search(model_pattern, line)
        if model_match:
            print(model_match.group(1))
            print(model_match.group(2))
            print(model_match.group(3))
            print(model_match.group(4))
            model = convert_var(model_match.group(1))
            route = convert_var(model_match.group(2))
            method = convert_var(model_match.group(4))
            return f'<form method="{method}" action="{{{{ route(\'{route}\') }}}}" >'
    return line

def convert_form_checkbox(line):
    if '{!! Form::checkbox' in line:
        checkbox_pattern = r"{!! Form::checkbox\(([^,]+),\s*([^,]+),\s*(\[.*?\]|.*?)\);?\s*!!}"
        checkbox_match = re.search(checkbox_pattern, line)
        if checkbox_match:
            name = convert_var(checkbox_match.group(1))
            value = convert_var(checkbox_match.group(2))
            attrs = checkbox_match.group(3)
            attrs_dict = {k: v for k, v in re.findall(r"([^']+)'\s*=>\s*'?([^',]+)", attrs)}
            attrs_str = ' '.join([f'{k}="{v}"' for k, v in attrs_dict.items()])
            return f'<input type="checkbox" name="{name}" value="{value}" {attrs_str}>'
    return line

def convert_form_textarea(line):
    if '{!! Form::textarea' in line:
        textarea_pattern = r"{!! Form::textarea\(([^,]+),\s*([^,]+),\s*(\[.*?\]|.*?)\);?\s*!!}"
        textarea_match = re.search(textarea_pattern, line)
        if textarea_match:
            name = convert_var(textarea_match.group(1))
            value = convert_var(textarea_match.group(2))
            attrs = textarea_match.group(3)
            attrs_dict = {k: v for k, v in re.findall(r"([^']+)'\s*=>\s*'?([^',]+)", attrs)}
            attrs_str = ' '.join([f'{k}="{v}"' for k, v in attrs_dict.items()])
            return f'<textarea name="{name}" {attrs_str}>{value}</textarea>'
    return line

def convert_form_email(line):
    if '{!! Form::email' in line:
        email_pattern = r"{!! Form::email\(([^,]+),\s*([^,]+),\s*(\[.*?\]|.*?)\);?\s*!!}"
        email_match = re.search(email_pattern, line)
        if email_match:
            name = convert_var(email_match.group(1))
            value = convert_var(email_match.group(2))
            attrs = email_match.group(3)
            attrs_dict = {k: v for k, v in re.findall(r"([^']+)'\s*=>\s*'?([^',]+)", attrs)}
            attrs_str = ' '.join([f'{k}="{v}"' for k, v in attrs_dict.items()])
            return f'<input type="email" name="{name}" value="{value}" {attrs_str}>'
    return line 

def convert_form_password(line):
    if '{!! Form::password' in line:
        password_pattern = r"{!! Form::password\(([^,]+),\s*([^,]+),\s*(\[.*?\]|.*?)\);?\s*!!}"
        password_match = re.search(password_pattern, line)
        if password_match:
            name = convert_var(password_match.group(1))
            value = convert_var(password_match.group(2))
            attrs = password_match.group(3)
            attrs_dict = {k: v for k, v in re.findall(r"([^']+)'\s*=>\s*'?([^',]+)", attrs)}
            attrs_str = ' '.join([f'{k}="{v}"' for k, v in attrs_dict.items()])
            return f'<input type="password" name="{name}" value="{value}" {attrs_str}>'
    return line 

def convert_form_date(line):
    if '{!! Form::date' in line:
        date_pattern = r"{!! Form::date\(([^,]+),\s*([^,]+),\s*(\[.*?\]|.*?)\);?\s*!!}"
        date_match = re.search(date_pattern, line)
        if date_match:
            name = convert_var(date_match.group(1))
            value = convert_var(date_match.group(2))
            attrs = date_match.group(3)
            attrs_dict = {k: v for k, v in re.findall(r"([^']+)'\s*=>\s*'?([^',]+)", attrs)}
            attrs_str = ' '.join([f'{k}="{v}"' for k, v in attrs_dict.items()])
            return f'<input type="date" name="{name}" value="{value}" {attrs_str}>'
    return line

def convert_file(file_path):
    with open(file_path, 'r', encoding='utf-8') as file:
        content = file.read()
    
    # Split into lines for processing
    lines = content.split('\n')
    converted_lines = []
    
    for line in lines:
        # Apply conversions in sequence
        new_line = line
        new_line = convert_form_open(new_line)
        new_line = convert_form_input(new_line)
        new_line = convert_form_select(new_line)
        new_line = convert_form_close(new_line)
        new_line = convert_form_label(new_line)
        new_line = convert_form_text(new_line)
        new_line = convert_form_textarea(new_line)
        new_line = convert_form_submit(new_line)
        new_line = convert_form_number(new_line)
        new_line = convert_form_hidden(new_line)
        new_line = convert_form_model(new_line)
        new_line = convert_form_checkbox(new_line)
        new_line = convert_form_email(new_line)
        new_line = convert_form_password(new_line)
        new_line = convert_form_date(new_line)
        converted_lines.append(new_line)
    
    # Create backup of original file
    backup_path = str(file_path) + '.bak'
    os.rename(file_path, backup_path)
    
    # Write converted content
    with open(file_path, 'w', encoding='utf-8') as file:
        file.write('\n'.join(converted_lines))

def main(file_path):
    # Base directory for views
    base_dir = Path('FamilyFund/app1/family-fund-app/resources/views')
    
    
    print(f'Converting {file_path}...')
    convert_file(file_path)
    print(f'Converted {file_path}')

if __name__ == '__main__':
    main(sys.argv[1]) 